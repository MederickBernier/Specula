<?php

namespace Database\Factories;

use App\Enums\FeedType;
use App\Models\FeedSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeedSource>
 */
class FeedSourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' blog',
            'url' => fake()->unique()->url().'/feed.xml',
            'feed_type' => FeedType::Rss,
            'is_active' => true,
            'last_fetched_at' => null,
            'last_error' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }
}
