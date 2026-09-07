<?php

namespace Database\Factories;

use App\Enums\TechnologyCategory;
use App\Enums\TechnologyRing;
use App\Enums\TechnologyStatus;
use App\Models\Technology;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Technology>
 */
class TechnologyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->word()),
            'category' => fake()->randomElement(TechnologyCategory::cases()),
            'ring' => TechnologyRing::Adopt,
            'status' => TechnologyStatus::Current,
            'vendor' => null,
            'homepage_url' => null,
            'notes' => null,
        ];
    }

    public function held(): static
    {
        return $this->state(fn (array $attributes): array => ['ring' => TechnologyRing::Hold]);
    }

    public function deprecated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TechnologyStatus::Deprecated,
        ]);
    }
}
