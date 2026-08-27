<?php

namespace App\Models;

use App\Enums\DecisionStatus;
use Carbon\CarbonImmutable;
use Database\Factories\DecisionRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $project_prefix
 * @property string $category
 * @property int $sequence
 * @property string $title
 * @property DecisionStatus $status
 * @property string $author
 * @property string|null $deciders
 * @property string|null $affects
 * @property string $proposal_context
 * @property string $recommendation
 * @property string|null $consequences
 * @property string|null $conditions_for_revisiting
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read string $document_id
 */
#[Fillable([
    'project_prefix',
    'category',
    'sequence',
    'title',
    'status',
    'author',
    'deciders',
    'affects',
    'proposal_context',
    'recommendation',
    'consequences',
    'conditions_for_revisiting',
])]
class DecisionRecord extends Model
{
    /** @use HasFactory<DecisionRecordFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $appends = ['document_id'];

    /**
     * @return array<string,string>
     */
    protected function casts():array{
        return [
            'status' => DecisionStatus::class,
        ];
    }

    /**
     * The formatted identifier, e.g. VNG-ARCH-001
     *
     * @return Attribute<string, never>
     */
    protected function documentId(): Attribute
    {
        return Attribute::get(fn (): string => $this->formatDocumentId());
    }

    private function formatDocumentId(): string
    {
        return sprintf(
            '%s-%s-%03d',
            $this->project_prefix,
            $this->category,
            $this->sequence,
        );
    }

    /**
     * @return HasMany<DecisionOption, $this>
     */
    public function options():HasMany{
        return $this->hasMany(DecisionOption::class);
    }

    /**
     * @return HasMany<DecisionLink, $this>
     */
    public function outgoingLinks():HasMany{
        return $this->hasMany(DecisionLink::class, 'source_id');
    }

    /**
     * @return HasMany<DecisionLink, $this>
     */
    public function incomingLinks():HasMany{
        return $this->hasMany(DecisionLink::class, 'target_id');
    }
}
