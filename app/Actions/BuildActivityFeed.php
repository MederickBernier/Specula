<?php

namespace App\Actions;

use App\Models\DecisionRecord;
use App\Models\ProjectNote;
use App\Models\Prototype;
use App\Models\SecurityNote;
use App\Models\VettingItem;

/**
 * What has been touched lately, across everything.
 *
 * A dashboard that only lists what is outstanding says nothing about whether
 * the week moved. Each module contributes its own recently changed records and
 * they are read in one order, which is the cheapest possible answer to "what
 * happened while I was not looking".
 *
 * @phpstan-type ActivityEvent array{
 *     kind: string,
 *     label: string,
 *     url: string,
 *     state: string|null,
 *     at: string
 * }
 */
class BuildActivityFeed
{
    /**
     * Each module offers this many before the merged list is trimmed, so one
     * busy module cannot crowd the others out of the running.
     */
    private const PER_MODULE = 6;

    /**
     * @return list<ActivityEvent>
     */
    public function __invoke(int $limit = 12): array
    {
        $events = [
            ...$this->decisions(),
            ...$this->vetting(),
            ...$this->prototypes(),
            ...$this->findings(),
            ...$this->notes(),
        ];

        usort($events, fn (array $a, array $b): int => $b['at'] <=> $a['at']);

        return array_slice($events, 0, $limit);
    }

    /**
     * @return list<ActivityEvent>
     */
    private function decisions(): array
    {
        return array_values(DecisionRecord::query()
            ->latest('updated_at')
            ->limit(self::PER_MODULE)
            ->get()
            ->map(fn (DecisionRecord $record): array => [
                'kind' => 'Decision',
                'label' => $record->document_id.' — '.$record->title,
                'url' => route('decisions.show', $record),
                'state' => $record->status->label(),
                'at' => (string) $record->updated_at,
            ])
            ->all());
    }

    /**
     * @return list<ActivityEvent>
     */
    private function vetting(): array
    {
        return array_values(VettingItem::query()
            ->latest('updated_at')
            ->limit(self::PER_MODULE)
            ->get()
            ->map(fn (VettingItem $item): array => [
                'kind' => 'Vetting',
                'label' => $item->title,
                'url' => route('vetting.show', $item),
                'state' => $item->status->label(),
                'at' => (string) $item->updated_at,
            ])
            ->all());
    }

    /**
     * @return list<ActivityEvent>
     */
    private function prototypes(): array
    {
        return array_values(Prototype::query()
            ->latest('updated_at')
            ->limit(self::PER_MODULE)
            ->get()
            ->map(fn (Prototype $prototype): array => [
                'kind' => 'Prototype',
                'label' => $prototype->title,
                'url' => route('prototypes.show', $prototype),
                'state' => $prototype->status->label(),
                'at' => (string) $prototype->updated_at,
            ])
            ->all());
    }

    /**
     * @return list<ActivityEvent>
     */
    private function findings(): array
    {
        return array_values(SecurityNote::query()
            ->latest('updated_at')
            ->limit(self::PER_MODULE)
            ->get()
            ->map(fn (SecurityNote $note): array => [
                'kind' => 'Security',
                'label' => $note->title,
                'url' => route('security-notes.show', $note),
                'state' => $note->severity->label().', '.$note->status->label(),
                'at' => (string) $note->updated_at,
            ])
            ->all());
    }

    /**
     * @return list<ActivityEvent>
     */
    private function notes(): array
    {
        return array_values(ProjectNote::query()
            ->latest('updated_at')
            ->limit(self::PER_MODULE)
            ->get()
            ->map(fn (ProjectNote $note): array => [
                'kind' => 'Note',
                'label' => $note->title,
                'url' => route('projects.show', $note->project_id),
                'state' => null,
                'at' => (string) $note->updated_at,
            ])
            ->all());
    }
}
