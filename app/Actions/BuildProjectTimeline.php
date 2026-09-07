<?php

namespace App\Actions;

use App\Enums\PrototypeStatus;
use App\Models\DecisionRecord;
use App\Models\Project;
use App\Models\ProjectNote;

/**
 * @phpstan-type TimelineEvent array{
 *     date: string,
 *     kind: string,
 *     event: string,
 *     label: string,
 *     url: string|null,
 *     meta: string|null
 * }
 *
 * The story of a project, assembled from the dates already recorded against it.
 *
 * Nothing new is stored: every event here is a column some module already
 * fills in. What was missing was reading them in one order, which is what you
 * need when someone asks where a project actually stands.
 */
class BuildProjectTimeline
{
    /**
     * @return list<TimelineEvent>
     */
    public function __invoke(Project $project): array
    {
        $events = [
            ...$this->projectEvents($project),
            ...$this->decisionEvents($project),
            ...$this->vettingEvents($project),
            ...$this->prototypeEvents($project),
            ...$this->securityEvents($project),
            ...$this->noteEvents($project),
        ];

        // Newest first: a timeline is read to find out what just happened.
        usort($events, fn (array $a, array $b): int => $b['date'] <=> $a['date']);

        return $events;
    }

    /**
     * @return list<TimelineEvent>
     */
    private function projectEvents(Project $project): array
    {
        $events = [[
            'date' => $project->created_at?->toDateString() ?? '',
            'kind' => 'Project',
            'event' => 'Project started',
            'label' => $project->name,
            'url' => null,
            'meta' => null,
        ]];

        if ($project->archived_at !== null) {
            $events[] = [
                'date' => $project->archived_at->toDateString(),
                'kind' => 'Project',
                'event' => 'Project archived',
                'label' => $project->name,
                'url' => null,
                'meta' => null,
            ];
        }

        return $events;
    }

    /**
     * @return list<TimelineEvent>
     */
    private function decisionEvents(Project $project): array
    {
        return array_values($project->decisionRecords()
            ->orderBy('created_at')
            ->get()
            ->map(fn (DecisionRecord $record): array => [
                'date' => $record->created_at?->toDateString() ?? '',
                'kind' => 'Decision',
                'event' => 'Decision recorded',
                'label' => $record->document_id.' — '.$record->title,
                'url' => route('decisions.show', $record),
                'meta' => $record->status->label(),
            ])
            ->all());
    }

    /**
     * A proposal raised, and separately the day it was settled, because the gap
     * between the two is the part worth seeing.
     *
     * @return list<TimelineEvent>
     */
    private function vettingEvents(Project $project): array
    {
        $events = [];

        foreach ($project->vettingItems()->orderBy('date_raised')->get() as $item) {
            $events[] = [
                'date' => $item->date_raised->toDateString(),
                'kind' => 'Vetting',
                'event' => 'Proposal raised',
                'label' => $item->title,
                'url' => route('vetting.show', $item),
                'meta' => null,
            ];

            if ($item->date_resolved !== null) {
                $events[] = [
                    'date' => $item->date_resolved->toDateString(),
                    'kind' => 'Vetting',
                    'event' => 'Proposal '.strtolower($item->status->label()),
                    'label' => $item->title,
                    'url' => route('vetting.show', $item),
                    'meta' => null,
                ];
            }
        }

        return $events;
    }

    /**
     * @return list<TimelineEvent>
     */
    private function prototypeEvents(Project $project): array
    {
        $events = [];

        foreach ($project->prototypes()->orderBy('date_started')->get() as $prototype) {
            $events[] = [
                'date' => $prototype->date_started->toDateString(),
                'kind' => 'Prototype',
                'event' => 'Spike started',
                'label' => $prototype->title,
                'url' => route('prototypes.show', $prototype),
                'meta' => null,
            ];

            if ($prototype->date_completed !== null) {
                $events[] = [
                    'date' => $prototype->date_completed->toDateString(),
                    'kind' => 'Prototype',
                    'event' => $prototype->status === PrototypeStatus::Abandoned
                        ? 'Spike abandoned'
                        : 'Spike finished',
                    'label' => $prototype->title,
                    'url' => route('prototypes.show', $prototype),
                    'meta' => $prototype->confidence_level?->label(),
                ];
            }
        }

        return $events;
    }

    /**
     * @return list<TimelineEvent>
     */
    private function securityEvents(Project $project): array
    {
        $events = [];

        foreach ($project->securityNotes()->orderBy('date_flagged')->get() as $note) {
            $events[] = [
                'date' => $note->date_flagged->toDateString(),
                'kind' => 'Security',
                'event' => 'Finding flagged',
                'label' => $note->title,
                'url' => route('security-notes.show', $note),
                'meta' => $note->severity->label(),
            ];

            if ($note->date_resolved !== null) {
                $events[] = [
                    'date' => $note->date_resolved->toDateString(),
                    'kind' => 'Security',
                    'event' => 'Finding '.strtolower($note->status->label()),
                    'label' => $note->title,
                    'url' => route('security-notes.show', $note),
                    'meta' => null,
                ];
            }
        }

        return $events;
    }

    /**
     * @return list<TimelineEvent>
     */
    private function noteEvents(Project $project): array
    {
        return array_values($project->notes()
            ->orderBy('created_at')
            ->get()
            ->map(fn (ProjectNote $note): array => [
                'date' => $note->created_at?->toDateString() ?? '',
                'kind' => 'Note',
                'event' => 'Note added',
                'label' => $note->title,
                'url' => route('projects.show', $note->project_id),
                'meta' => null,
            ])
            ->all());
    }
}
