<?php

namespace App\Models;

use App\Enums\TechnologyCategory;
use App\Enums\TechnologyRing;
use App\Enums\TechnologyStatus;
use Carbon\CarbonImmutable;
use Database\Factories\TechnologyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Something the estate is built from: a language, a datastore, a service.
 *
 * @property int $id
 * @property string $name
 * @property TechnologyCategory $category
 * @property TechnologyRing $ring
 * @property TechnologyStatus $status
 * @property string|null $vendor
 * @property string|null $homepage_url
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'category', 'ring', 'status', 'vendor', 'homepage_url', 'notes'])]
class Technology extends Model
{
    /** @use HasFactory<TechnologyFactory> */
    use HasFactory;

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'category' => TechnologyCategory::class,
            'ring' => TechnologyRing::class,
            'status' => TechnologyStatus::class,
        ];
    }

    /**
     * @return HasMany<TechnologyUsage, $this>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(TechnologyUsage::class);
    }
}
