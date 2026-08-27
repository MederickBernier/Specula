<?php

namespace App\Models;

use Carbon\CarbonImmutable;
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

#[Fillable(['name','description','pros','cons','was_chosen'])]

class DecisionOption extends Model
{
    /** @use HasFactory<\Database\Factories\DecisionOptionFactory> */
    use HasFactory;

    /**
     * @return array<string,string>
     */
    protected function casts():array{
        return[
            'was_chosen' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<DecisionRecord, $this>
     */
    public function decisionRecord():BelongsTo{
        return $this->belongsTo(DecisionRecord::class);
    }
}
