<?php

namespace App\Concerns;

use App\Contracts\Linkable;
use App\Enums\ItemLinkType;
use App\Models\ItemLink;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Builds the cross-module link payload a module's show page renders.
 *
 * Every linkable module presents its links the same way, so the shape is
 * defined once here rather than in each controller.
 */
trait PresentsItemLinks
{
    /**
     * The link payload every module's show page needs.
     *
     * @return array<string, mixed>
     */
    protected function itemLinkProps(Linkable&Model $record): array
    {
        return [
            'itemLinks' => $this->itemLinksFor($record),
            'itemLinkTargets' => $this->itemLinkTargets($record),
            'itemLinkTypes' => ItemLinkType::options(),
            'itemLinkSource' => [
                'type' => $record->getMorphClass(),
                'id' => $record->getKey(),
            ],
        ];
    }

    /**
     * @return array{outgoing: list<array<string,mixed>>, incoming: list<array<string,mixed>>}
     */
    protected function itemLinksFor(Linkable&Model $record): array
    {
        return [
            'outgoing' => array_values($this->linksAt('source', $record)
                ->map(fn (ItemLink $link): array => $this->presentLink($link, $link->target))
                ->all()),
            'incoming' => array_values($this->linksAt('target', $record)
                ->map(fn (ItemLink $link): array => $this->presentLink($link, $link->source))
                ->all()),
        ];
    }

    /**
     * Every other record this one could be linked to, grouped by module.
     *
     * @return list<array{type: string, label: string, records: list<array{id: int, label: string}>}>
     */
    protected function itemLinkTargets(Linkable&Model $record): array
    {
        $groups = [];

        foreach (ItemLink::modules() as $type => $class) {
            $records = [];

            foreach ($class::query()->get() as $candidate) {
                if (! $candidate instanceof Linkable || $candidate->is($record)) {
                    continue;
                }

                $records[] = [
                    'id' => (int) $candidate->getKey(),
                    'label' => $candidate->linkLabel(),
                ];
            }

            if ($records === []) {
                continue;
            }

            $groups[] = [
                'type' => $type,
                'label' => $class::moduleLabel(),
                'records' => $records,
            ];
        }

        return $groups;
    }

    /**
     * The links where the record sits at the given end.
     *
     * Queried directly rather than through the morph relations so the presenter
     * only needs a Linkable, not knowledge of each module's relation methods.
     *
     * @return Collection<int, ItemLink>
     */
    private function linksAt(string $end, Linkable&Model $record)
    {
        $other = $end === 'source' ? 'target' : 'source';

        return ItemLink::query()
            ->where($end.'_type', $record->getMorphClass())
            ->where($end.'_id', $record->getKey())
            ->with($other)
            ->get();
    }

    /**
     * @return array<string,mixed>
     */
    private function presentLink(ItemLink $link, ?Model $other): array
    {
        return [
            'id' => $link->id,
            'link_type' => $link->link_type->value,
            'link_type_label' => $link->link_type->label(),
            'note' => $link->note,
            'date_linked' => $link->date_linked,
            'other' => $other instanceof Linkable ? [
                'module' => $other::moduleLabel(),
                'label' => $other->linkLabel(),
                'url' => $other->linkUrl(),
            ] : null,
        ];
    }
}
