<?php

namespace App\Models;

use App\Enums\FeedType;
use Carbon\CarbonImmutable;
use Database\Factories\FeedSourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $url
 * @property FeedType $feed_type
 * @property bool $is_active
 * @property CarbonImmutable|null $last_fetched_at
 * @property string|null $last_error
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'url', 'feed_type', 'is_active'])]
class FeedSource extends Model
{
    /** @use HasFactory<FeedSourceFactory> */
    use HasFactory;

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'feed_type' => FeedType::class,
            'is_active' => 'boolean',
            'last_fetched_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<RadarItem, $this>
     */
    public function radarItems(): HasMany
    {
        return $this->hasMany(RadarItem::class);
    }
}
