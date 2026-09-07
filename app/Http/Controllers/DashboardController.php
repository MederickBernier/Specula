<?php

namespace App\Http\Controllers;

use App\Enums\DecisionStatus;
use App\Enums\PrototypeStatus;
use App\Enums\SecurityNoteStatus;
use App\Enums\SecuritySeverity;
use App\Enums\TriageStatus;
use App\Enums\VettingStatus;
use App\Models\DecisionRecord;
use App\Models\FeedSource;
use App\Models\Prototype;
use App\Models\RadarItem;
use App\Models\SecurityNote;
use App\Models\VettingItem;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * @var list<array{kind: string, label: string, url: string, due_at: string|null}>|null
     */
    private ?array $dueForReview = null;

    /**
     * The landing surface: what is waiting on you, module by module.
     *
     * Everything here counts open work rather than totals. A tally of records
     * ever created is not something you act on; a queue is.
     */
    public function __invoke(): Response
    {
        return Inertia::render('dashboard', [
            'stats' => $this->stats(),
            'queues' => $this->queues(),
        ]);
    }

    /**
     * @return list<array{key: string, label: string, value: int, hint: string, url: string}>
     */
    private function stats(): array
    {
        return [
            [
                'key' => 'radar',
                'label' => 'Awaiting triage',
                'value' => RadarItem::query()->where('triage_status', TriageStatus::Pending)->count(),
                'hint' => 'radar items',
                'url' => route('radar.index'),
            ],
            [
                'key' => 'vetting',
                'label' => 'Open proposals',
                'value' => VettingItem::query()
                    ->whereNull('date_resolved')
                    ->count(),
                'hint' => 'in the vetting log',
                'url' => route('vetting.index'),
            ],
            [
                'key' => 'prototypes',
                'label' => 'Prototypes running',
                'value' => Prototype::query()
                    ->whereIn('status', [PrototypeStatus::Planned, PrototypeStatus::InProgress])
                    ->count(),
                'hint' => 'planned or in progress',
                'url' => route('prototypes.index'),
            ],
            [
                'key' => 'security',
                'label' => 'Findings open',
                'value' => SecurityNote::query()->whereNull('date_resolved')->count(),
                'hint' => 'not yet remediated',
                'url' => route('security-notes.index'),
            ],
            [
                'key' => 'review',
                'label' => 'Due for review',
                'value' => count($this->dueForReview()),
                'hint' => 'asked to be revisited',
                'url' => route('dashboard'),
            ],
            [
                'key' => 'decisions',
                'label' => 'Decisions unsettled',
                'value' => DecisionRecord::query()
                    ->whereIn('status', [DecisionStatus::Draft, DecisionStatus::UnderRework])
                    ->count(),
                'hint' => 'draft or under rework',
                'url' => route('decisions.index'),
            ],
        ];
    }

    /**
     * The short lists worth acting on today.
     *
     * @return array<string, mixed>
     */
    private function queues(): array
    {
        return [
            'severeFindings' => SecurityNote::query()
                ->whereNull('date_resolved')
                ->where('is_issue', true)
                ->whereIn('severity', [SecuritySeverity::Critical, SecuritySeverity::High])
                ->orderByRaw('case when severity = ? then 0 else 1 end', [SecuritySeverity::Critical->value])
                ->orderBy('date_flagged')
                ->limit(5)
                ->get(['id', 'title', 'severity', 'status', 'date_flagged'])
                ->all(),
            'needsPrototype' => VettingItem::query()
                ->where('status', VettingStatus::NeedsPrototype)
                ->orderBy('date_raised')
                ->limit(5)
                ->get(['id', 'title', 'date_raised'])
                ->all(),
            'dueForReview' => array_slice($this->dueForReview(), 0, 8),
            'deferredFindings' => SecurityNote::query()
                ->where('status', SecurityNoteStatus::Deferred)
                ->whereNull('deferred_until')
                ->orderBy('date_flagged')
                ->limit(5)
                ->get(['id', 'title', 'date_flagged'])
                ->all(),
            'staleFeeds' => FeedSource::query()
                ->where('is_active', true)
                ->whereNotNull('last_error')
                ->orderBy('name')
                ->limit(5)
                ->get(['id', 'name', 'last_error'])
                ->all(),
        ];
    }

    /**
     * Everything that asked to be looked at again by now, oldest first.
     *
     * A decision records the conditions for revisiting it and a deferred
     * finding records why it was put off; both now carry a date, and this is
     * where that date comes back.
     *
     * @return list<array{kind: string, label: string, url: string, due_at: string|null}>
     */
    private function dueForReview(): array
    {
        // Asked for twice on one render: once for the count, once for the list.
        if ($this->dueForReview !== null) {
            return $this->dueForReview;
        }

        $today = CarbonImmutable::now();

        $decisions = DecisionRecord::query()
            ->dueForReview($today)
            ->orderBy('next_review_at')
            ->get(['id', 'project_prefix', 'category', 'sequence', 'title', 'next_review_at'])
            ->map(fn (DecisionRecord $record): array => [
                'kind' => 'Decision',
                'label' => $record->document_id.' — '.$record->title,
                'url' => route('decisions.show', $record),
                'due_at' => $record->next_review_at?->toDateString(),
            ]);

        $findings = SecurityNote::query()
            ->deferralElapsed($today)
            ->orderBy('deferred_until')
            ->get(['id', 'title', 'deferred_until'])
            ->map(fn (SecurityNote $note): array => [
                'kind' => 'Finding',
                'label' => $note->title,
                'url' => route('security-notes.show', $note),
                'due_at' => $note->deferred_until?->toDateString(),
            ]);

        return $this->dueForReview = array_values(
            $decisions->concat($findings)->sortBy('due_at')->all(),
        );
    }
}
