<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Technology;
use App\Models\TechnologyUsage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<TechnologyUsage>
 */
class TechnologyUsageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'technology_id' => Technology::factory(),
            // Closures rather than values: an overridden key is never expanded,
            // so on() does not leave a stray project behind.
            'usable_type' => fn (): string => 'project',
            'usable_id' => fn (): Factory => Project::factory(),
            'version' => null,
            'role' => null,
            'notes' => null,
        ];
    }

    public function on(Model $record): static
    {
        return $this->state(fn (array $attributes): array => [
            'usable_type' => $record->getMorphClass(),
            'usable_id' => $record->getKey(),
        ]);
    }
}
