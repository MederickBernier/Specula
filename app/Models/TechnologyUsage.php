<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\TechnologyUsageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One place a technology is used, and at which version.
 *
 * @property int $id
 * @property int $technology_id
 * @property string $usable_type
 * @property int $usable_id
 * @property string|null $version
 * @property string|null $role
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['technology_id', 'usable_type', 'usable_id', 'version', 'role', 'notes'])]
class TechnologyUsage extends Model
{
    /** @use HasFactory<TechnologyUsageFactory> */
    use HasFactory;

    /**
     * The records that can carry a technology, keyed by the alias stored in the
     * morph column.
     *
     * A project holds a stack; a prototype is often built with something the
     * project does not use; a decision is where a technology was chosen or
     * refused; a finding is raised against one, which is what makes exposure
     * per technology answerable.
     *
     * @return array<string, class-string<Model>>
     */
    public static function carriers(): array
    {
        return [
            'project' => Project::class,
            'prototype' => Prototype::class,
            'decision_record' => DecisionRecord::class,
            'security_note' => SecurityNote::class,
        ];
    }

    /**
     * @return BelongsTo<Technology, $this>
     */
    public function technology(): BelongsTo
    {
        return $this->belongsTo(Technology::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function usable(): MorphTo
    {
        return $this->morphTo();
    }
}
