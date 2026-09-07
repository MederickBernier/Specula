<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ProjectNoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 * @property string $title
 * @property string $body
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['title', 'body'])]
class ProjectNote extends Model
{
    /** @use HasFactory<ProjectNoteFactory> */
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
            'title' => 'encrypted',
            'body' => 'encrypted',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
