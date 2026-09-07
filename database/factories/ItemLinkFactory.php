<?php

namespace Database\Factories;

use App\Enums\ItemLinkType;
use App\Models\ItemLink;
use App\Models\VettingItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<ItemLink>
 */
class ItemLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_type' => 'vetting_item',
            'source_id' => VettingItem::factory(),
            'target_type' => 'vetting_item',
            'target_id' => VettingItem::factory(),
            'link_type' => ItemLinkType::RelatedTo,
            'note' => null,
        ];
    }

    /**
     * @param  Model  $source
     * @param  Model  $target
     */
    public function between($source, $target): static
    {
        return $this->state(fn (array $attributes): array => [
            'source_type' => $source->getMorphClass(),
            'source_id' => $source->getKey(),
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->getKey(),
        ]);
    }
}
