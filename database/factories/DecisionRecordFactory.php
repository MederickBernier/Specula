<?php

namespace Database\Factories;

use App\Enums\DecisionStatus;
use App\Models\DecisionRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DecisionRecord>
 */
class DecisionRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_prefix' => 'VNG',
            'category' => fake()->randomElement(['ARCH', 'LANG', 'INFRA']),
            'sequence' => fake()->unique()->numberBetween(1, 999),
            'title' => fake()->sentence(4),
            'status' => DecisionStatus::Draft,
            'author' => fake()->name(),
            'deciders' => 'N/A',
            'affects' => fake()->words(3, true),
            'proposal_context' => fake()->paragraph(),
            'recommendation' => fake()->paragraph(),
            'consequences' => null,
            'conditions_for_revisiting' => null,
        ];
    }

    public function decided():static{
        return $this->state(fn(array $attributes):array =>[
            'status' => DecisionStatus::Decided,
        ]);
    }

    public function superseded():static{
        return $this->state(fn(array $attributes):array =>[
            'status' => DecisionStatus::Superseded,
        ]);
    }
}
