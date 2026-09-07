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
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
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
            'deferredFindings' => SecurityNote::query()
                ->where('status', SecurityNoteStatus::Deferred)
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
}
