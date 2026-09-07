<?php

namespace Database\Factories;

use App\Enums\SecurityNoteSource;
use App\Enums\SecurityNoteStatus;
use App\Enums\SecurityRoutedTo;
use App\Enums\SecuritySeverity;
use App\Models\SecurityNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SecurityNote>
 */
class SecurityNoteFactory extends Factory
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
            'source' => fake()->randomElement(SecurityNoteSource::cases()),
            'category' => fake()->randomElement(['SSRF', 'IAM', 'credential exposure', 'supply chain']),
            'severity' => fake()->randomElement(SecuritySeverity::cases()),
            'finding' => fake()->paragraph(),
            'is_issue' => true,
            'non_issue_reason' => null,
            'routed_to' => SecurityRoutedTo::Unrouted,
            'status' => SecurityNoteStatus::Flagged,
            'deferral_reason' => null,
            'date_flagged' => fake()->dateTimeBetween('-6 months'),
            'external_url' => null,
        ];
    }

    public function routed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SecurityNoteStatus::Routed,
            'routed_to' => SecurityRoutedTo::WebTeamLead,
        ]);
    }

    public function remediated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SecurityNoteStatus::Remediated,
            'routed_to' => SecurityRoutedTo::SelfHandled,
        ]);
    }

    public function deferred(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SecurityNoteStatus::Deferred,
            'deferral_reason' => fake()->sentence(),
        ]);
    }

    public function nonIssue(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SecurityNoteStatus::NonIssue,
            'is_issue' => false,
            'non_issue_reason' => fake()->sentence(),
        ]);
    }
}
