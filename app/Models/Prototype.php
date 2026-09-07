<?php

namespace App\Models;

use App\Concerns\HasItemLinks;
use App\Contracts\Linkable;
use App\Enums\ConfidenceLevel;
use App\Enums\PrototypeStatus;
use Carbon\CarbonImmutable;
use Database\Factories\PrototypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $project_id
 * @property string $title
 * @property PrototypeStatus $status
 * @property string $hypothesis
 * @property string|null $test_approach
 * @property string|null $result
 * @property string|null $abandoned_reason
 * @property ConfidenceLevel|null $confidence_level
 * @property bool|null $is_reusable
 * @property string|null $reusability_note
 * @property string|null $repo_reference
 * @property CarbonImmutable $date_started
 * @property CarbonImmutable|null $date_completed
 * @property-read Project|null $project
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'project_id',
    'title',
    'status',
    'hypothesis',
    'test_approach',
    'result',
    'abandoned_reason',
    'confidence_level',
    'is_reusable',
    'reusability_note',
    'repo_reference',
    'date_started',
])]
class Prototype extends Model implements Linkable
{
    /** @use HasFactory<PrototypeFactory> */
    use HasFactory;

    use HasItemLinks;

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'status' => PrototypeStatus::class,
            'confidence_level' => ConfidenceLevel::class,
            'is_reusable' => 'boolean',
            'date_started' => 'date',
            'date_completed' => 'date',
        ];
    }

    /**
     * Keep date_completed in step with the status for every writer, the same
     * way VettingItem tracks its resolution date. An existing date is left
     * alone; a prototype put back in progress loses it.
     */
    protected static function booted(): void
    {
        static::saving(function (self $prototype): void {
            if ($prototype->status->isFinished()) {
                $prototype->date_completed ??= now();

                return;
            }

            $prototype->date_completed = null;
        });
    }

    public function linkLabel(): string
    {
        return $this->title;
    }

    public function linkUrl(): string
    {
        return route('prototypes.show', $this);
    }

    public static function moduleLabel(): string
    {
        return 'Prototype';
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
