<?php

namespace App\Actions;

use App\Models\Project;
use Illuminate\Support\Str;

/**
 * Renders a whole project as one markdown document: what it is, the decisions
 * taken on it in full, and the notes kept alongside them.
 *
 * The other modules are summarised rather than reproduced. A vetting item or a
 * spike is working material; the decisions are the thing worth handing to
 * someone else.
 */
class RenderProjectMarkdown
{
    public function __construct(private RenderDecisionRecordMarkdown $decision) {}

    public function __invoke(Project $project): string
    {
        $project->loadMissing([
            'decisionRecords.options',
            'decisionRecords.outgoingLinks.target',
            'decisionRecords.incomingLinks.source',
            'notes',
        ]);

        $lines = ['# '.$project->prefix.' — '.$project->name];

        if ($project->isArchived()) {
            $lines[] = '';
            $lines[] = '*Archived '.$project->archived_at?->toDateString().'.*';
        }

        if ($project->description !== null && trim($project->description) !== '') {
            $lines[] = '';
            $lines[] = trim($project->description);
        }

        $this->decisions($lines, $project);
        $this->openWork($lines, $project);
        $this->notes($lines, $project);

        return implode("\n", $lines)."\n";
    }

    public function filename(Project $project): string
    {
        return Str::slug($project->prefix).'.md';
    }

    /**
     * @param  list<string>  $lines
     */
    private function decisions(array &$lines, Project $project): void
    {
        $decisions = $project->decisionRecords
            ->sortBy(['category', 'sequence'])
            ->values();

        if ($decisions->isEmpty()) {
            return;
        }

        $lines[] = '';
        $lines[] = '## Decision records';

        foreach ($decisions as $record) {
            $lines[] = '';
            $lines[] = rtrim(($this->decision)($record, 3));
        }
    }

    /**
     * A short account of what is still moving, so the document says where the
     * project actually is rather than only what was settled.
     *
     * @param  list<string>  $lines
     */
    private function openWork(array &$lines, Project $project): void
    {
        $rows = [];

        foreach ($project->vettingItems()->whereNull('date_resolved')->orderBy('date_raised')->get() as $item) {
            $rows[] = ['Vetting', $item->title, $item->status->label()];
        }

        foreach ($project->prototypes()->whereNull('date_completed')->orderBy('date_started')->get() as $prototype) {
            $rows[] = ['Prototype', $prototype->title, $prototype->status->label()];
        }

        foreach ($project->securityNotes()->whereNull('date_resolved')->orderBy('date_flagged')->get() as $note) {
            $rows[] = ['Security', $note->title, $note->severity->label().', '.$note->status->label()];
        }

        if ($rows === []) {
            return;
        }

        $lines[] = '';
        $lines[] = '## Still open';
        $lines[] = '';
        $lines[] = '| Kind | Item | State |';
        $lines[] = '|---|---|---|';

        foreach ($rows as [$kind, $title, $state]) {
            $lines[] = sprintf('| %s | %s | %s |', $kind, str_replace('|', '\\|', $title), $state);
        }
    }

    /**
     * @param  list<string>  $lines
     */
    private function notes(array &$lines, Project $project): void
    {
        if ($project->notes->isEmpty()) {
            return;
        }

        $lines[] = '';
        $lines[] = '## Notes';

        foreach ($project->notes->sortByDesc('updated_at') as $note) {
            $lines[] = '';
            $lines[] = '### '.$note->title;
            $lines[] = '';
            $lines[] = trim($note->body);
        }
    }
}
