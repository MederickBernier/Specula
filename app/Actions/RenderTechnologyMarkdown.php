<?php

namespace App\Actions;

use App\Concerns\WritesMarkdownDocuments;
use App\Contracts\ExportsToMarkdown;
use App\Models\Technology;
use App\Models\TechnologyUsage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * A technology as a document: what we think of it, and everywhere it runs.
 *
 * The table of uses is the part worth handing to someone, because the spread of
 * versions across projects is what an upgrade conversation actually turns on.
 */
class RenderTechnologyMarkdown implements ExportsToMarkdown
{
    use WritesMarkdownDocuments;

    public function __invoke(Model $record, int $headingLevel = 1): string
    {
        if (! $record instanceof Technology) {
            throw new InvalidArgumentException('Expected a technology.');
        }

        $h = str_repeat('#', $headingLevel);

        $lines = [$h.' '.$record->name];

        $this->metadata($lines, [
            ['Category', $record->category->label()],
            ['Ring', $record->ring->label().' — '.$record->ring->description()],
            ['Status', $record->status->label().' — '.$record->status->description()],
            ['Vendor', $record->vendor],
            ['Homepage', $record->homepage_url],
        ]);

        $this->section($lines, $h.'# Notes', $record->notes);
        $this->uses($lines, $record, $h);

        return implode("\n", $lines)."\n";
    }

    public function basename(Model $record): string
    {
        return Str::slug($record->getAttribute('name'));
    }

    /**
     * @param  list<string>  $lines
     */
    private function uses(array &$lines, Technology $technology, string $h): void
    {
        $usages = $technology->usages()->with('usable')->get();

        if ($usages->isEmpty()) {
            return;
        }

        $lines[] = '';
        $lines[] = $h.'# Where it is used';
        $lines[] = '';
        $lines[] = '| Kind | Record | Version | Role |';
        $lines[] = '|---|---|---|---|';

        foreach ($usages as $usage) {
            $lines[] = sprintf(
                '| %s | %s | %s | %s |',
                $this->kind($usage),
                $this->cell(BuildTechnologyBreakdown::describe($usage->usable)),
                $this->cell($usage->version),
                $this->cell($usage->role),
            );
        }
    }

    private function kind(TechnologyUsage $usage): string
    {
        return match ($usage->usable_type) {
            'project' => 'Project',
            'prototype' => 'Prototype',
            'decision_record' => 'Decision',
            'security_note' => 'Finding',
            default => $usage->usable_type,
        };
    }
}
