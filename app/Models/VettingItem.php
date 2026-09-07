<?php

namespace App\Models;

use App\Concerns\HasItemLinks;
use App\Contracts\Linkable;
use App\Enums\VettingSourceType;
use App\Enums\VettingStatus;
use Carbon\CarbonImmutable;
use Database\Factories\VettingItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $project_id
 * @property string $title
 * @property VettingSourceType $source_type
 * @property string|null $source_detail
 * @property CarbonImmutable $date_raised
 * @property string $proposal_description
 * @property string|null $assessment
 * @property VettingStatus $status
 * @property string|null $rejection_reason
 * @property CarbonImmutable|null $date_resolved
 * @property string|null $external_url
 * @property-read Project|null $project
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'project_id',
    'title',
    'source_type',
    'source_detail',
    'date_raised',
    'proposal_description',
    'assessment',
    'status',
    'rejection_reason',
    'external_url',
])]
class VettingItem extends Model implements Linkable
{
    /** @use HasFactory<VettingItemFactory> */
    use HasFactory;

    use HasItemLinks;

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            // Encrypted at rest: this is the substance of the work, and a
            // database that leaves the machine should not carry it in the
            // clear. Statuses and dates stay readable so the app can still
            // sort, filter and count on them.
            'title' => 'encrypted',
            'source_detail' => 'encrypted',
            'proposal_description' => 'encrypted',
            'assessment' => 'encrypted',
            'rejection_reason' => 'encrypted',
            'external_url' => 'encrypted',
            'source_type' => VettingSourceType::class,
            'status' => VettingStatus::class,
            'date_raised' => 'date',
            'date_resolved' => 'date',
        ];
    }

    /**
     * Keep date_resolved in step with the status for every writer, rather than
     * asking each caller to remember it. Deliberately does not overwrite a
     * resolution date that is already set.
     */
    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            if ($item->status->isResolved()) {
                $item->date_resolved ??= now();

                return;
            }

            $item->date_resolved = null;
        });
    }

    public function linkLabel(): string
    {
        return $this->title;
    }

    public function linkUrl(): string
    {
        return route('vetting.show', $this);
    }

    public static function moduleLabel(): string
    {
        return 'Vetting item';
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
