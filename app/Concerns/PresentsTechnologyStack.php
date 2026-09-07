<?php

namespace App\Concerns;

use App\Models\Technology;
use App\Models\TechnologyUsage;
use Illuminate\Database\Eloquent\Model;

/**
 * The stack payload a record's show page renders.
 *
 * Every carrier presents it the same way, so the shape is defined once here.
 */
trait PresentsTechnologyStack
{
    /**
     * @return array<string, mixed>
     */
    protected function technologyStackProps(Model $record): array
    {
        $usages = TechnologyUsage::query()
            ->with('technology')
            ->where('usable_type', $record->getMorphClass())
            ->where('usable_id', $record->getKey())
            ->get()
            ->sortBy(fn (TechnologyUsage $usage): string => (string) $usage->technology?->name)
            ->values();

        return [
            'stack' => $usages
                ->map(fn (TechnologyUsage $usage): array => [
                    'id' => $usage->id,
                    'name' => (string) $usage->technology?->name,
                    'url' => route('technologies.show', $usage->technology_id),
                    'category' => (string) $usage->technology?->category->value,
                    'version' => $usage->version,
                    'role' => $usage->role,
                ])
                ->all(),
            'technologyOptions' => Technology::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Technology $technology): array => [
                    'value' => (string) $technology->id,
                    'label' => $technology->name,
                ])
                ->all(),
            'stackTarget' => [
                'type' => $record->getMorphClass(),
                'id' => $record->getKey(),
            ],
        ];
    }
}
