<?php

namespace App\Models;

use App\Concerns\HasItemLinks;
use App\Contracts\Linkable;
use App\Enums\SecurityNoteSource;
use App\Enums\SecurityNoteStatus;
use App\Enums\SecurityRoutedTo;
use App\Enums\SecuritySeverity;
use Carbon\CarbonImmutable;
use Database\Factories\SecurityNoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $project_id
 * @property string $title
 * @property SecurityNoteSource $source
 * @property string|null $category
 * @property SecuritySeverity $severity
 * @property string $finding
 * @property bool $is_issue
 * @property string|null $non_issue_reason
 * @property SecurityRoutedTo $routed_to
 * @property SecurityNoteStatus $status
 * @property string|null $deferral_reason
 * @property CarbonImmutable $date_flagged
 * @property CarbonImmutable|null $date_resolved
 * @property string|null $external_url
 * @property-read Project|null $project
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'project_id',
    'title',
    'source',
    'category',
    'severity',
    'finding',
    'is_issue',
    'non_issue_reason',
    'routed_to',
    'status',
    'deferral_reason',
    'date_flagged',
    'external_url',
])]
class SecurityNote extends Model implements Linkable
{
    /** @use HasFactory<SecurityNoteFactory> */
    use HasFactory;

    use HasItemLinks;

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'source' => SecurityNoteSource::class,
            'severity' => SecuritySeverity::class,
            'routed_to' => SecurityRoutedTo::class,
            'status' => SecurityNoteStatus::class,
            'is_issue' => 'boolean',
            'date_flagged' => 'date',
            'date_resolved' => 'date',
        ];
    }

    /**
     * Keep date_resolved in step with the status for every writer, the same way
     * VettingItem and Prototype track their own closing dates. A deferred
     * finding is still open, so it keeps no resolution date.
     */
    protected static function booted(): void
    {
        static::saving(function (self $note): void {
            if ($note->status->isResolved()) {
                $note->date_resolved ??= now();

                return;
            }

            $note->date_resolved = null;
        });
    }

    public function linkLabel(): string
    {
        return $this->title;
    }

    public function linkUrl(): string
    {
        return route('security-notes.show', $this);
    }

    public static function moduleLabel(): string
    {
        return 'Security note';
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
