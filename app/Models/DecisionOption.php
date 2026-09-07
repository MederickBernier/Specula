<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DecisionOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $decision_record_id
 * @property string $name
 * @property string|null $description
 * @property string|null $pros
 * @property string|null $cons
 * @property bool $was_chosen
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'description', 'pros', 'cons', 'was_chosen'])]

class DecisionOption extends Model
{
    /** @use HasFactory<DecisionOptionFactory> */
    use HasFactory;

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
            'name' => 'encrypted',
            'description' => 'encrypted',
            'pros' => 'encrypted',
            'cons' => 'encrypted',
            'was_chosen' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<DecisionRecord, $this>
     */
    public function decisionRecord(): BelongsTo
    {
        return $this->belongsTo(DecisionRecord::class);
    }
}
