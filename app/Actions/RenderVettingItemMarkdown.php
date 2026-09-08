<?php

namespace App\Actions;

use App\Concerns\WritesMarkdownDocuments;
use App\Contracts\ExportsToMarkdown;
use App\Models\VettingItem;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * A proposal as a document: where it came from, what was proposed, what the
 * assessment concluded, and why it was refused if it was.
 */
class RenderVettingItemMarkdown implements ExportsToMarkdown
{
    use WritesMarkdownDocuments;

    public function __invoke(Model $record, int $headingLevel = 1): string
    {
        if (! $record instanceof VettingItem) {
            throw new InvalidArgumentException('Expected a vetting item.');
        }

        $h = str_repeat('#', $headingLevel);

        $lines = [$h.' '.$record->title];

        $this->metadata($lines, [
            ['Source', $record->source_type->label()],
            ['Raised by', $record->source_detail],
            ['Status', $record->status->label()],
            ['Raised', $record->date_raised->toDateString()],
            ['Resolved', $record->date_resolved?->toDateString()],
            ['Link', $record->external_url],
            ['Project', $record->project?->prefix],
        ]);

        $this->section($lines, $h.'# Proposal', $record->proposal_description);
        $this->section($lines, $h.'# Assessment', $record->assessment);
        $this->section($lines, $h.'# Why it was rejected', $record->rejection_reason);

        return implode("\n", $lines)."\n";
    }

    public function basename(Model $record): string
    {
        return 'vetting-'.$record->getKey();
    }
}
