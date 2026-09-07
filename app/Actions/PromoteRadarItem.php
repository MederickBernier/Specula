<?php

namespace App\Actions;

use App\Enums\ItemLinkType;
use App\Enums\TriageStatus;
use App\Enums\VettingSourceType;
use App\Enums\VettingStatus;
use App\Models\FeedSource;
use App\Models\ItemLink;
use App\Models\RadarItem;
use App\Models\VettingItem;
use Illuminate\Support\Facades\DB;

/**
 * Turns a radar item into a vetting item, linked back to where it came from.
 *
 * This is the chain the app exists to record: something surfaces on the radar,
 * gets assessed in the vetting log, and the trail between the two survives.
 */
class PromoteRadarItem
{
    public function __invoke(RadarItem $item): VettingItem
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

            ItemLink::create([
                'source_type' => $item->getMorphClass(),
                'source_id' => $item->getKey(),
                'target_type' => $vettingItem->getMorphClass(),
                'target_id' => $vettingItem->getKey(),
                'link_type' => ItemLinkType::ResultedIn,
                'note' => __('Raised from the tech radar'),
            ]);

            // Promoting is itself a judgement that the item mattered, so an
            // untriaged item stops sitting in the queue. An item already
            // triaged keeps the note that was written for it.
            if ($item->triage_status === TriageStatus::Pending) {
                $item->update([
                    'triage_status' => TriageStatus::Relevant,
                    'relevance_note' => __('Promoted to the vetting log.'),
                ]);
            }

            return $vettingItem;
        });
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
     * Whether this item has already produced a vetting item.
     */
    public function alreadyPromoted(RadarItem $item): bool
    {
        return ItemLink::query()
            ->where('source_type', $item->getMorphClass())
            ->where('source_id', $item->getKey())
            ->where('target_type', (new VettingItem)->getMorphClass())
            ->exists();
    }
}
