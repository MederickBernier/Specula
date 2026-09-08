<?php

namespace App\Actions;

use App\Concerns\WritesMarkdownDocuments;
use App\Models\Project;
use App\Models\Prototype;
use App\Models\SecurityNote;
use App\Models\Technology;
use App\Models\VettingItem;
use Carbon\CarbonImmutable;

/**
 * Everything, as one document.
 *
 * Projects carry most of it, since each already nests its own decisions, but
 * work filed under no project is gathered explicitly rather than left out: a
 * complete archive that quietly omits things is worse than no archive.
 *
 * ponytail: renders the whole estate in one request. Right at this size; if it
 * ever outgrows a request, the answer is a queued job writing to storage, not a
 * longer timeout.
 */
class RenderEverythingMarkdown
{
    use WritesMarkdownDocuments;

    public function __construct(
        private RenderProjectMarkdown $project,
        private RenderVettingItemMarkdown $vetting,
        private RenderPrototypeMarkdown $prototype,
        private RenderSecurityNoteMarkdown $finding,
    ) {}

    public function __invoke(): string
    {
        $lines = [
            '# Specula',
            '',
            '*Everything recorded, as of '.CarbonImmutable::now()->toDateString().'.*',
        ];

        $this->projects($lines);
        $this->unfiled($lines);
        $this->technologies($lines);

        return implode("\n", $lines)."\n";
    }

    public function basename(): string
    {
        return 'specula-'.CarbonImmutable::now()->toDateString();
    }

    /**
     * @param  list<string>  $lines
     */
    private function projects(array &$lines): void
    {
        $projects = Project::query()
            ->get()
            ->sortBy(fn (Project $project): string => ($project->isArchived() ? '1' : '0').$project->name)
            ->values();

        if ($projects->isEmpty()) {
            return;
        }

        foreach ($projects as $project) {
            $lines[] = '';
            $lines[] = rtrim(($this->project)($project, 2));
        }
    }

    /**
     * Work that belongs to no project, which the project sections cannot reach.
     *
     * @param  list<string>  $lines
     */
    private function unfiled(array &$lines): void
    {
        $vetting = VettingItem::query()->whereNull('project_id')->orderBy('date_raised')->get();
        $prototypes = Prototype::query()->whereNull('project_id')->orderBy('date_started')->get();
        $findings = SecurityNote::query()->whereNull('project_id')->orderBy('date_flagged')->get();

        if ($vetting->isEmpty() && $prototypes->isEmpty() && $findings->isEmpty()) {
            return;
        }

        $lines[] = '';
        $lines[] = '## Filed under no project';

        foreach ($vetting as $item) {
            $lines[] = '';
            $lines[] = rtrim(($this->vetting)($item, 3));
        }

        foreach ($prototypes as $prototype) {
            $lines[] = '';
            $lines[] = rtrim(($this->prototype)($prototype, 3));
        }

        foreach ($findings as $finding) {
            $lines[] = '';
            $lines[] = rtrim(($this->finding)($finding, 3));
        }
    }

    /**
     * The inventory as one table, rather than a document per technology: at
     * this point in the archive what matters is the shape of the estate.
     *
     * @param  list<string>  $lines
     */
    private function technologies(array &$lines): void
    {
        $technologies = Technology::query()
            ->withCount('usages')
            ->get()
            ->sortBy(fn (Technology $technology): string => $technology->category->label().$technology->name)
            ->values();

        if ($technologies->isEmpty()) {
            return;
        }

        $lines[] = '';
        $lines[] = '## Technology inventory';
        $lines[] = '';
        $lines[] = '| Technology | Category | Ring | Status | Used in |';
        $lines[] = '|---|---|---|---|---|';

        foreach ($technologies as $technology) {
            $lines[] = sprintf(
                '| %s | %s | %s | %s | %d |',
                $this->cell($technology->name),
                $technology->category->label(),
                $technology->ring->label(),
                $technology->status->label(),
                (int) $technology->getAttribute('usages_count'),
            );
        }
    }
}
