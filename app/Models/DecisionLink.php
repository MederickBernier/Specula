<?php

namespace App\Models;

use App\Enums\DecisionRelationshipType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
/**
 * @property int $id;
 * @property int $source_id
 * @property int $target_id
 * @property DecisionRelationshipType $relationship_type
 * @property string|null $scope_note
 * @property string|null $role_note
 * @property string|null $impact_summary
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */

#[Fillable(['source_id', 'target_id','relationship_type','scope_note','role_note','impact_summary'])]

class DecisionLink extends Model
{
    /** @use HasFactory<\Database\Factories\DecisionLinkFactory> */
    use HasFactory;

    /**
     * @return array<string,string>
     */
    protected function casts():array{
        return[
            'relationship_type'=>DecisionRelationshipType::class,
        ];
    }

    /**
     * @return BelongsTo<DecisionRecord, $this>
     */
    public function source():BelongsTo{
        return $this->belongsTo(DecisionRecord::class, 'source_id');
    }

    /**
     * @return BelongsTo<DecisionRecord, $this>
     */
    public function target():BelongsTo{
        return $this->belongsTo(DecisionRecord::class, 'target_id');
    }
}
