<?php

namespace App\Actions;

use App\Models\DecisionRecord;

/**
 * Renders a decision record back to the markdown document it was written as.
 *
 * An ADR exists to be read somewhere else: pasted into a pull request, a wiki
 * or an email. Keeping the fields as rows is what makes them queryable; this
 * puts them back together on the way out.
 */
class RenderDecisionRecordMarkdown
{
    public function __invoke(DecisionRecord $record, int $headingLevel = 1): string
    {
        $record->loadMissing(['options', 'outgoingLinks.target', 'incomingLinks.source']);

        $h = str_repeat('#', $headingLevel);

        $lines = [
            $h.' '.$record->document_id.' — '.$record->title,
            '',
            '| | |',
            '|---|---|',
            '| Status | '.$record->status->label().' |',
            '| Author | '.$this->cell($record->author).' |',
            '| Deciders | '.$this->cell($record->deciders).' |',
            '| Affects | '.$this->cell($record->affects).' |',
            '| Created | '.$record->created_at?->toDateString().' |',
            '| Updated | '.$record->updated_at?->toDateString().' |',
        ];

        $this->section($lines, $h.'# Context', $record->proposal_context);
        $this->section($lines, $h.'# Decision', $record->recommendation);
        $this->options($lines, $record, $h);
        $this->section($lines, $h.'# Consequences', $record->consequences);
        $this->section($lines, $h.'# Conditions for revisiting', $record->conditions_for_revisiting);
        $this->links($lines, $record, $h);

        return implode("\n", $lines)."\n";
    }

    /**
     * The filename this document should be saved as.
     */
    public function filename(DecisionRecord $record): string
    {
        return $record->document_id.'.md';
    }

    /**
     * @param  list<string>  $lines
     */
    private function section(array &$lines, string $heading, ?string $body): void
    {
        if ($body === null || trim($body) === '') {
            return;
        }

        $lines[] = '';
        $lines[] = $heading;
        $lines[] = '';
        $lines[] = trim($body);
    }

    /**
     * @param  list<string>  $lines
     */
    private function options(array &$lines, DecisionRecord $record, string $h): void
    {
        if ($record->options->isEmpty()) {
            return;
        }

        $lines[] = '';
        $lines[] = $h.'# Options considered';

        foreach ($record->options as $option) {
            $lines[] = '';
            $lines[] = $h.'## '.$option->name.($option->was_chosen ? ' *(chosen)*' : '');

            $this->optionPart($lines, $option->description);
            $this->optionPart($lines, $option->pros, '**Pros:** ');
            $this->optionPart($lines, $option->cons, '**Cons:** ');
        }
    }

    /**
     * @param  list<string>  $lines
     */
    private function optionPart(array &$lines, ?string $body, string $prefix = ''): void
    {
        if ($body === null || trim($body) === '') {
            return;
        }

        $lines[] = '';
        $lines[] = $prefix.trim($body);
    }

    /**
     * The cross-reference table that would otherwise be a hand-maintained
     * document of its own.
     *
     * @param  list<string>  $lines
     */
    private function links(array &$lines, DecisionRecord $record, string $h): void
    {
        $rows = [];

        foreach ($record->outgoingLinks as $link) {
            $rows[] = ['→', $link->relationship_type->label(), $link->target, $link];
        }

        foreach ($record->incomingLinks as $link) {
            $rows[] = ['←', $link->relationship_type->label(), $link->source, $link];
        }

        if ($rows === []) {
            return;
        }

        $lines[] = '';
        $lines[] = $h.'# Related decisions';
        $lines[] = '';
        $lines[] = '| | Relationship | Record | Scope | Role |';
        $lines[] = '|---|---|---|---|---|';

        foreach ($rows as [$direction, $type, $other, $link]) {
            $lines[] = sprintf(
                '| %s | %s | %s | %s | %s |',
                $direction,
                $type,
                $other instanceof DecisionRecord ? $other->document_id.' — '.$other->title : '—',
                $this->cell($link->scope_note),
                $this->cell($link->role_note),
            );
        }
    }

    /**
     * Keeps a pipe in free text from breaking the table it sits in.
     */
    private function cell(?string $value): string
    {
        $value = trim((string) $value);

        return $value === '' ? '—' : str_replace('|', '\\|', $value);
    }
}
