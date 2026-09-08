<?php

namespace App\Actions;

use App\Concerns\WritesMarkdownDocuments;
use App\Contracts\ExportsToMarkdown;
use App\Models\SecurityNote;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * A finding as a document: what was found, the triage call on it, and where it
 * went.
 */
class RenderSecurityNoteMarkdown implements ExportsToMarkdown
{
    use WritesMarkdownDocuments;

    public function __invoke(Model $record, int $headingLevel = 1): string
    {
        if (! $record instanceof SecurityNote) {
            throw new InvalidArgumentException('Expected a security note.');
        }

        $h = str_repeat('#', $headingLevel);

        $lines = [$h.' '.$record->title];

        $this->metadata($lines, [
            ['Severity', $record->severity->label()],
            ['Source', $record->source->label()],
            ['Category', $record->category],
            ['A real issue', $record->is_issue ? 'Yes' : 'No'],
            ['Routed to', $record->routed_to->label()],
            ['Status', $record->status->label()],
            ['Flagged', $record->date_flagged->toDateString()],
            ['Resolved', $record->date_resolved?->toDateString()],
            ['Deferred until', $record->deferred_until?->toDateString()],
            ['Link', $record->external_url],
            ['Project', $record->project?->prefix],
        ]);

        $this->section($lines, $h.'# Finding', $record->finding);
        $this->section($lines, $h.'# Why it is not an issue', $record->non_issue_reason);
        $this->section($lines, $h.'# Why it was deferred', $record->deferral_reason);

        return implode("\n", $lines)."\n";
    }

    public function basename(Model $record): string
    {
        return 'finding-'.$record->getKey();
    }
}
