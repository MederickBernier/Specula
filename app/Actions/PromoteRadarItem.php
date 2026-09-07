<?php

namespace App\Actions;

use App\Enums\ItemLinkType;
use App\Enums\PrototypeStatus;
use App\Enums\TriageStatus;
use App\Enums\VettingSourceType;
use App\Enums\VettingStatus;
use App\Models\FeedSource;
use App\Models\ItemLink;
use App\Models\Prototype;
use App\Models\RadarItem;
use App\Models\VettingItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Turns a radar item into work, linked back to where it came from.
 *
 * This is the chain the app exists to record: something surfaces on the radar,
 * and either gets assessed in the vetting log or goes straight to a spike
 * because it is simply worth trying. Either way the trail survives.
 */
class PromoteRadarItem
{
    public function toVettingItem(RadarItem $item): VettingItem
    {
        return DB::transaction(function () use ($item): VettingItem {
            $vettingItem = VettingItem::create([
                'title' => $item->title,
                'source_type' => VettingSourceType::TechRadar,
                // A radar item outlives the feed it came from, so the source
                // name may no longer be there to record.
                'source_detail' => $item->feedSource instanceof FeedSource
                    ? $item->feedSource->name
                    : __('Tech radar'),
                'date_raised' => now(),
                'proposal_description' => $this->description($item),
                'status' => VettingStatus::New,
                'external_url' => $item->url,
            ]);

            $this->link($item, $vettingItem, __('Raised from the tech radar'));
            $this->markTriaged($item, __('Promoted to the vetting log.'));

            return $vettingItem;
        });
    }

    public function toPrototype(RadarItem $item): Prototype
    {
        return DB::transaction(function () use ($item): Prototype {
            $prototype = Prototype::create([
                'title' => $item->title,
                'status' => PrototypeStatus::Planned,
                'hypothesis' => $this->description($item),
                'date_started' => now(),
            ]);

            $this->link($item, $prototype, __('Worth trying, from the tech radar'));
            $this->markTriaged($item, __('Promoted to a prototype.'));

            return $prototype;
        });
    }

    /**
     * @param  Model  $target
     */
    private function link(RadarItem $item, $target, string $note): void
    {
        ItemLink::create([
            'source_type' => $item->getMorphClass(),
            'source_id' => $item->getKey(),
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->getKey(),
            'link_type' => ItemLinkType::ResultedIn,
            'note' => $note,
        ]);
    }

    /**
     * Promoting is itself a judgement that the item mattered, so an untriaged
     * item stops sitting in the queue. An item already triaged keeps the note
     * that was written for it.
     */
    private function markTriaged(RadarItem $item, string $note): void
    {
        if ($item->triage_status !== TriageStatus::Pending) {
            return;
        }

        $item->update([
            'triage_status' => TriageStatus::Relevant,
            'relevance_note' => $note,
        ]);
    }

    /**
     * The proposal starts as what the feed said, with a link back to the source.
     */
    private function description(RadarItem $item): string
    {
        $lines = [];

        if ($item->summary !== null) {
            $lines[] = $item->summary;
            $lines[] = '';
        }

        $lines[] = '['.$item->title.']('.$item->url.')';

        return implode("\n", $lines);
    }

    /**
     * Whether this item has already produced work of the given kind.
     */
    public function alreadyPromoted(RadarItem $item, string $targetType): bool
    {
        return ItemLink::query()
            ->where('source_type', $item->getMorphClass())
            ->where('source_id', $item->getKey())
            ->where('target_type', $targetType)
            ->exists();
    }
}
