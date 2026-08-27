<?php

namespace Database\Factories;

use App\Models\DecisionOption;
use App\Models\DecisionRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DecisionOption>
 */
class DecisionOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'decision_record_id' => DecisionRecord::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->paragraph(),
            'pros' => fake()->sentence(),
            'cons' => fake()->sentence(),
            'was_chosen' => false,
        ];
    }

    public function chosen():static{
        return $this->state(fn (array $attributes):array => [
            'was_chosen' => true,
        ]);
    }
}
