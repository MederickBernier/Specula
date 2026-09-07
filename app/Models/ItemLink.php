<?php

namespace App\Models;

use App\Contracts\Linkable;
use App\Enums\ItemLinkType;
use Carbon\CarbonImmutable;
use Database\Factories\ItemLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A link between two records in different modules, e.g. a radar item that
 * spawned a vetting item, or a prototype that resulted in a decision record.
 *
 * @property int $id
 * @property string $source_type
 * @property int $source_id
 * @property string $target_type
 * @property int $target_id
 * @property ItemLinkType $link_type
 * @property string|null $note
 * @property CarbonImmutable $date_linked
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['source_type', 'source_id', 'target_type', 'target_id', 'link_type', 'note'])]
class ItemLink extends Model
{
    /** @use HasFactory<ItemLinkFactory> */
    use HasFactory;

    /**
     * The modules that can appear at either end of a link, keyed by the alias
     * stored in the morph columns. Registered as the morph map in
     * AppServiceProvider, and the same list the link UI offers as targets.
     *
     * @return array<string, class-string<Linkable&Model>>
     */
    public static function modules(): array
    {
        return [
            'decision_record' => DecisionRecord::class,
            'vetting_item' => VettingItem::class,
            'prototype' => Prototype::class,
            'security_note' => SecurityNote::class,
            'radar_item' => RadarItem::class,
        ];
    }

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
            'note' => 'encrypted',
            'link_type' => ItemLinkType::class,
            'date_linked' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $link): void {
            $link->date_linked ??= now();
        });
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}
