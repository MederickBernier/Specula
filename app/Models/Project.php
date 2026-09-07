<?php

namespace App\Models;

use App\Concerns\HasTechnologies;
use Carbon\CarbonImmutable;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A body of work that the other modules hang off: its decisions, the proposals
 * vetted for it, the spikes run for it, the findings raised against it, and the
 * loose notes that are none of those.
 *
 * @property int $id
 * @property string $name
 * @property string $prefix
 * @property string|null $description
 * @property CarbonImmutable|null $archived_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'prefix', 'description'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    use HasTechnologies;

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'archived_at' => 'immutable_datetime',
        ];
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * @param  Builder<Project>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /**
     * @param  Builder<Project>  $query
     */
    public function scopeArchived(Builder $query): void
    {
        $query->whereNotNull('archived_at');
    }

    /**
     * @return HasMany<DecisionRecord, $this>
     */
    public function decisionRecords(): HasMany
    {
        return $this->hasMany(DecisionRecord::class);
    }

    /**
     * @return HasMany<VettingItem, $this>
     */
    public function vettingItems(): HasMany
    {
        return $this->hasMany(VettingItem::class);
    }

    /**
     * @return HasMany<Prototype, $this>
     */
    public function prototypes(): HasMany
    {
        return $this->hasMany(Prototype::class);
    }

    /**
     * @return HasMany<SecurityNote, $this>
     */
    public function securityNotes(): HasMany
    {
        return $this->hasMany(SecurityNote::class);
    }

    /**
     * @return HasMany<ProjectNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(ProjectNote::class);
    }
}
