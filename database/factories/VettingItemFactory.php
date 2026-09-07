<?php

namespace Database\Factories;

use App\Enums\VettingSourceType;
use App\Enums\VettingStatus;
use App\Models\VettingItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VettingItem>
 */
class VettingItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'source_type' => fake()->randomElement(VettingSourceType::cases()),
            'source_detail' => fake()->name(),
            'date_raised' => fake()->dateTimeBetween('-6 months'),
            'proposal_description' => fake()->paragraph(),
            'assessment' => null,
            'status' => VettingStatus::New,
            'rejection_reason' => null,
            'external_url' => null,
        ];
    }

    public function vetted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => VettingStatus::Vetted,
            'assessment' => fake()->paragraph(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => VettingStatus::Rejected,
            'assessment' => fake()->paragraph(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    public function needsPrototype(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => VettingStatus::NeedsPrototype,
            'assessment' => fake()->paragraph(),
        ]);
    }
}
