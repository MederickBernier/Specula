<?php

namespace App\Models;

use App\Enums\SecurityNoteSource;
use App\Enums\SecurityNoteStatus;
use App\Enums\SecurityRoutedTo;
use App\Enums\SecuritySeverity;
use Carbon\CarbonImmutable;
use Database\Factories\SecurityNoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
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
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
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
class SecurityNote extends Model
{
    /** @use HasFactory<SecurityNoteFactory> */
    use HasFactory;

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
}
