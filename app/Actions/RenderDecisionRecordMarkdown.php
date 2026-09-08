<?php

namespace App\Actions;

use App\Concerns\WritesMarkdownDocuments;
use App\Contracts\ExportsToMarkdown;
use App\Models\DecisionRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Renders a decision record back to the markdown document it was written as.
 *
 * An ADR exists to be read somewhere else: pasted into a pull request, a wiki
 * or an email. Keeping the fields as rows is what makes them queryable; this
 * puts them back together on the way out.
 */
class RenderDecisionRecordMarkdown implements ExportsToMarkdown
{
    use WritesMarkdownDocuments;

    public function __invoke(Model $record, int $headingLevel = 1): string
    {
        if (! $record instanceof DecisionRecord) {
            throw new InvalidArgumentException('Expected a decision record.');
        }

        $record->loadMissing(['options', 'outgoingLinks.target', 'incomingLinks.source']);

        $h = $this->heading($headingLevel);
        $section = $this->heading($headingLevel + 1);
        $option = $this->heading($headingLevel + 2);

        $lines = [
            $h.' '.$record->document_id.' — '.$record->title,
        ];

        $this->metadata($lines, [
            ['Status', $record->status->label()],
            ['Author', $record->author],
            ['Deciders', $record->deciders],
            ['Affects', $record->affects],
            ['Created', $record->created_at?->toDateString()],
            ['Updated', $record->updated_at?->toDateString()],
        ]);

        $this->section($lines, $section.' Context', $record->proposal_context);
        $this->section($lines, $section.' Decision', $record->recommendation);
        $this->options($lines, $record, $section, $option);
        $this->section($lines, $section.' Consequences', $record->consequences);
        $this->section($lines, $section.' Conditions for revisiting', $record->conditions_for_revisiting);
        $this->links($lines, $record, $section);

        return implode("\n", $lines)."\n";
    }

    public function basename(Model $record): string
    {
        return (string) $record->getAttribute('document_id');
    }

    /**
     * @param  list<string>  $lines
     */
    private function options(array &$lines, DecisionRecord $record, string $section, string $option): void
    {
        if ($record->options->isEmpty()) {
            return;
        }

        $lines[] = '';
        $lines[] = $section.' Options considered';

        foreach ($record->options as $candidate) {
            $lines[] = '';
            $lines[] = $option.' '.$candidate->name.($candidate->was_chosen ? ' *(chosen)*' : '');

            $this->optionPart($lines, $candidate->description);
            $this->optionPart($lines, $candidate->pros, '**Pros:** ');
            $this->optionPart($lines, $candidate->cons, '**Cons:** ');
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
    private function links(array &$lines, DecisionRecord $record, string $section): void
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
        $lines[] = $section.' Related decisions';
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
}
