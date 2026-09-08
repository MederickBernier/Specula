<?php

namespace App\Actions;

use App\Concerns\WritesMarkdownDocuments;
use App\Contracts\ExportsToMarkdown;
use App\Enums\PrototypeStatus;
use App\Models\Prototype;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * A spike as a document: what it set out to prove, how it was tried, and how
 * it landed.
 */
class RenderPrototypeMarkdown implements ExportsToMarkdown
{
    use WritesMarkdownDocuments;

    public function __invoke(Model $record, int $headingLevel = 1): string
    {
        if (! $record instanceof Prototype) {
            throw new InvalidArgumentException('Expected a prototype.');
        }

        $h = str_repeat('#', $headingLevel);

        $lines = [$h.' '.$record->title];

        $this->metadata($lines, [
            ['Status', $record->status->label()],
            ['Confidence', $record->confidence_level?->label()],
            ['Reusable', $this->reusable($record)],
            ['Repository', $record->repo_reference],
            ['Started', $record->date_started->toDateString()],
            ['Finished', $record->date_completed?->toDateString()],
            ['Project', $record->project?->prefix],
        ]);

        $this->section($lines, $h.'# Hypothesis', $record->hypothesis);
        $this->section($lines, $h.'# Test approach', $record->test_approach);

        if ($record->status === PrototypeStatus::Abandoned) {
            $this->section($lines, $h.'# Why it was abandoned', $record->abandoned_reason);
        } else {
            $this->section($lines, $h.'# Result', $record->result);
        }

        $this->section($lines, $h.'# What can be reused', $record->reusability_note);

        return implode("\n", $lines)."\n";
    }

    public function basename(Model $record): string
    {
        return 'prototype-'.$record->getKey();
    }

    /**
     * Null while the spike is still running, which is different from a no.
     */
    private function reusable(Prototype $record): ?string
    {
        return match ($record->is_reusable) {
            true => 'Yes',
            false => 'No',
            default => null,
        };
    }
}
