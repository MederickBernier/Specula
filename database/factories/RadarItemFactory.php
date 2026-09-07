<?php

namespace Database\Factories;

use App\Enums\TriageStatus;
use App\Models\FeedSource;
use App\Models\RadarItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RadarItem>
 */
class RadarItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'feed_source_id' => FeedSource::factory(),
            'title' => fake()->sentence(6),
            'url' => fake()->unique()->url(),
            'published_at' => fake()->dateTimeBetween('-3 months'),
            'triage_status' => TriageStatus::Pending,
            'relevance_note' => null,
        ];
    }

    public function relevant(): static
    {
        return $this->state(fn (array $attributes): array => [
            'triage_status' => TriageStatus::Relevant,
            'relevance_note' => fake()->sentence(),
        ]);
    }

    public function discarded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'triage_status' => TriageStatus::Discarded,
        ]);
    }
}
