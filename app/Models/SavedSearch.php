<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SavedSearchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A named set of radar filters, private to the person who saved it.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property array<string, string> $filters
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'filters'])]
class SavedSearch extends Model
{
    /** @use HasFactory<SavedSearchFactory> */
    use HasFactory;

    /**
     * The radar filters a saved search is allowed to carry.
     *
     * Anything else in the request is dropped, so a saved search can never
     * smuggle an unexpected query parameter back into the index.
     *
     * @var list<string>
     */
    public const FILTER_KEYS = ['q', 'feed', 'status'];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'filters' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
