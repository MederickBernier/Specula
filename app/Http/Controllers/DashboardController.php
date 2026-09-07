<?php

namespace App\Http\Controllers;

use App\Actions\BuildActivityFeed;
use App\Enums\DecisionStatus;
use App\Enums\PrototypeStatus;
use App\Enums\SecurityNoteStatus;
use App\Enums\SecuritySeverity;
use App\Enums\TriageStatus;
use App\Enums\VettingStatus;
use App\Models\DecisionRecord;
use App\Models\FeedSource;
use App\Models\Project;
use App\Models\Prototype;
use App\Models\RadarItem;
use App\Models\SecurityNote;
use App\Models\VettingItem;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The landing surface, built around two questions: what wants me now, and what
 * moved while I was not looking.
 *
 * Everything urgent is merged into one ranked list rather than split across a
 * card per module. Four cards that are each usually empty read as an empty
 * dashboard; one list with three things in it reads as three things to do.
 */
class DashboardController extends Controller
{
    /**
     * @var list<array{kind: string, label: string, url: string, why: string, at: string|null}>|null
     */
    private ?array $attention = null;

    public function __invoke(BuildActivityFeed $activity): Response
    {
        return Inertia::render('dashboard', [
            'attention' => $this->attention(),
            'counts' => $this->counts(),
            'projects' => $this->projects(),
            'activity' => $activity(),
        ]);
    }

    /**
     * Everything asking for a decision from you, most pressing first.
     *
     * The order is deliberate: a critical finding outranks a review that came
     * due, which outranks a feed that stopped answering.
     *
     * @return list<array{kind: string, label: string, url: string, why: string, at: string|null}>
     */
    private function attention(): array
    {
        if ($this->attention !== null) {
            return $this->attention;
        }

        $today = CarbonImmutable::now();
        $items = [];

        foreach (SecurityNote::query()
            ->whereNull('date_resolved')
            ->where('is_issue', true)
            ->whereIn('severity', [SecuritySeverity::Critical, SecuritySeverity::High])
            ->orderByRaw('case when severity = ? then 0 else 1 end', [SecuritySeverity::Critical->value])
            ->orderBy('date_flagged')
            ->get() as $note) {
            $items[] = [
                'kind' => 'Finding',
                'label' => $note->title,
                'url' => route('security-notes.show', $note),
                'why' => $note->severity->label().', open since '.$note->date_flagged->toDateString(),
                'at' => $note->date_flagged->toDateString(),
            ];
        }

        foreach (DecisionRecord::query()
            ->dueForReview($today)
            ->orderBy('next_review_at')
            ->get() as $record) {
            $items[] = [
                'kind' => 'Decision',
                'label' => $record->document_id.' — '.$record->title,
                'url' => route('decisions.show', $record),
                'why' => 'Due to be read again',
                'at' => $record->next_review_at?->toDateString(),
            ];
        }

        foreach (SecurityNote::query()
            ->deferralElapsed($today)
            ->orderBy('deferred_until')
            ->get() as $note) {
            $items[] = [
                'kind' => 'Finding',
                'label' => $note->title,
                'url' => route('security-notes.show', $note),
                'why' => 'Deferred until '.$note->deferred_until?->toDateString().', which has passed',
                'at' => $note->deferred_until?->toDateString(),
            ];
        }

        foreach (SecurityNote::query()
            ->where('status', SecurityNoteStatus::Deferred)
            ->whereNull('deferred_until')
            ->orderBy('date_flagged')
            ->get() as $note) {
            $items[] = [
                'kind' => 'Finding',
                'label' => $note->title,
                'url' => route('security-notes.show', $note),
                'why' => 'Deferred with no date to come back to',
                'at' => null,
            ];
        }

        foreach (VettingItem::query()
            ->where('status', VettingStatus::NeedsPrototype)
            ->orderBy('date_raised')
            ->get() as $item) {
            $items[] = [
                'kind' => 'Proposal',
                'label' => $item->title,
                'url' => route('vetting.show', $item),
                'why' => 'Waiting on a prototype',
                'at' => $item->date_raised->toDateString(),
            ];
        }

        foreach (FeedSource::query()
            ->where('is_active', true)
            ->whereNotNull('last_error')
            ->orderBy('name')
            ->get() as $feed) {
            $items[] = [
                'kind' => 'Feed',
                'label' => $feed->name,
                'url' => route('radar.feeds.index'),
                'why' => 'Last fetch failed: '.$feed->last_error,
                'at' => null,
            ];
        }

        return $this->attention = $this->mergeByRecord($items);
    }

    /**
     * A count with the word that fits it, so a project never reads "1 proposals".
     *
     * @return array{label: string, value: int}
     */
    private function openCount(string $singular, string $plural, Project $project, string $attribute): array
    {
        $value = (int) $project->getAttribute($attribute);

        return ['label' => $value === 1 ? $singular : $plural, 'value' => $value];
    }

    /**
     * One row per record, however many reasons it has.
     *
     * A critical finding whose deferral has also run out is one thing to deal
     * with, not two, so the reasons are joined and the highest priority one
     * decides where the row sits.
     *
     * @param  list<array{kind: string, label: string, url: string, why: string, at: string|null}>  $items
     * @return list<array{kind: string, label: string, url: string, why: string, at: string|null}>
     */
    private function mergeByRecord(array $items): array
    {
        $merged = [];

        foreach ($items as $item) {
            $key = $item['url'].'|'.$item['label'];

            if (isset($merged[$key])) {
                $merged[$key]['why'] .= ' · '.$item['why'];

                continue;
            }

            $merged[$key] = $item;
        }

        return array_values($merged);
    }

    /**
     * Open work, never lifetime totals: a tally of everything ever recorded is
     * not something anyone acts on.
     *
     * @return list<array{key: string, label: string, value: int, hint: string, url: string}>
     */
    private function counts(): array
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
                'value' => VettingItem::query()->whereNull('date_resolved')->count(),
                'hint' => 'in the vetting log',
                'url' => route('vetting.index'),
            ],
            [
                'key' => 'prototypes',
                'label' => 'Spikes running',
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
                'hint' => 'not yet resolved',
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
     * Active projects and what is still moving in each, so the dashboard can be
     * read a project at a time rather than only a module at a time.
     *
     * @return list<array<string, mixed>>
     */
    private function projects(): array
    {
        return array_values(Project::query()
            ->active()
            ->withCount([
                'decisionRecords as unsettled_count' => fn ($query) => $query
                    ->whereIn('status', [DecisionStatus::Draft, DecisionStatus::UnderRework]),
                'vettingItems as open_vetting_count' => fn ($query) => $query
                    ->whereNull('date_resolved'),
                'prototypes as running_count' => fn ($query) => $query
                    ->whereIn('status', [PrototypeStatus::Planned, PrototypeStatus::InProgress]),
                'securityNotes as open_findings_count' => fn ($query) => $query
                    ->whereNull('date_resolved'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'prefix' => $project->prefix,
                'url' => route('projects.show', $project),
                'open' => [
                    $this->openCount('decision', 'decisions', $project, 'unsettled_count'),
                    $this->openCount('proposal', 'proposals', $project, 'open_vetting_count'),
                    $this->openCount('spike', 'spikes', $project, 'running_count'),
                    $this->openCount('finding', 'findings', $project, 'open_findings_count'),
                ],
            ])
            ->all());
    }
}
