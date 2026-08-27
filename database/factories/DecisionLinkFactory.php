<?php

namespace Database\Factories;

use App\Enums\DecisionRelationshipType;
use App\Models\DecisionLink;
use App\Models\DecisionRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DecisionLink>
 */
class DecisionLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_id' => DecisionRecord::factory(),
            'target_id' => DecisionRecord::factory(),
            'relationship_type' => DecisionRelationshipType::RelatedTo,
            'scope_note' => null,
            'role_note' => null,
            'impact_summary' => null,
        ];
    }
}
