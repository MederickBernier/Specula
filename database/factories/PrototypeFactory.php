<?php

namespace Database\Factories;

use App\Enums\ConfidenceLevel;
use App\Enums\PrototypeStatus;
use App\Models\Prototype;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prototype>
 */
class PrototypeFactory extends Factory
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
            'status' => PrototypeStatus::Planned,
            'hypothesis' => fake()->paragraph(),
            'test_approach' => null,
            'result' => null,
            'abandoned_reason' => null,
            'confidence_level' => null,
            'is_reusable' => null,
            'reusability_note' => null,
            'repo_reference' => null,
            'date_started' => fake()->dateTimeBetween('-6 months'),
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PrototypeStatus::InProgress,
            'test_approach' => fake()->paragraph(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PrototypeStatus::Completed,
            'test_approach' => fake()->paragraph(),
            'result' => fake()->paragraph(),
            'confidence_level' => ConfidenceLevel::Medium,
            'is_reusable' => false,
        ]);
    }

    public function reusable(): static
    {
        return $this->completed()->state(fn (array $attributes): array => [
            'is_reusable' => true,
            'reusability_note' => fake()->sentence(),
            'repo_reference' => 'spike/'.fake()->slug(2),
        ]);
    }

    public function abandoned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PrototypeStatus::Abandoned,
            'test_approach' => fake()->paragraph(),
            'abandoned_reason' => fake()->sentence(),
        ]);
    }
}
