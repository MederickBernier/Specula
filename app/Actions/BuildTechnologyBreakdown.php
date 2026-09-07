<?php

namespace App\Actions;

use App\Enums\TechnologyCategory;
use App\Models\Project;
use App\Models\Technology;
use App\Models\TechnologyUsage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * The recap: what the estate is built from, and where each piece of it runs.
 *
 * The shape is a grid rather than a list because the questions a lead actually
 * asks are comparisons — where else do we run this, what does that project
 * depend on, what are we still running that we have said not to start with.
 */
class BuildTechnologyBreakdown
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        $projects = Project::query()
            ->get(['id', 'name', 'prefix', 'archived_at'])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
        $technologies = Technology::query()->orderBy('name')->get();
        $usages = TechnologyUsage::query()->with('usable')->get();

        $byTechnology = $usages->groupBy('technology_id');

        $rows = $technologies->map(function (Technology $technology) use ($byTechnology, $projects): array {
            $mine = $byTechnology->get($technology->id) ?? collect();

            $inProjects = [];

            foreach ($projects as $project) {
                $usage = $mine->first(fn (TechnologyUsage $usage): bool => $usage->usable_type === 'project'
                    && $usage->usable_id === $project->id);

                if ($usage !== null) {
                    $inProjects[$project->id] = $usage->version ?? '';
                }
            }

            $elsewhere = $mine
                ->filter(fn (TechnologyUsage $usage): bool => $usage->usable_type !== 'project')
                ->groupBy('usable_type')
                ->map(fn ($group): int => $group->count())
                ->all();

            return [
                'id' => $technology->id,
                'name' => $technology->name,
                'category' => $technology->category->value,
                'categoryLabel' => $technology->category->label(),
                'ring' => $technology->ring->value,
                'ringLabel' => $technology->ring->label(),
                'status' => $technology->status->value,
                'statusLabel' => $technology->status->label(),
                'url' => route('technologies.show', $technology),
                'projects' => $inProjects,
                'elsewhere' => $elsewhere,
                'total' => $mine->count(),
            ];
        });

        return [
            'projects' => $projects
                ->map(fn (Project $project): array => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'prefix' => $project->prefix,
                    'archived' => $project->archived_at !== null,
                    'url' => route('projects.show', $project),
                ])
                ->all(),
            // Grouped by category so the grid reads as an inventory rather than
            // an alphabet.
            'categories' => $this->groupByCategory(array_values($rows->all())),
            'summary' => $this->summary(array_values($rows->all()), $usages),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{category: string, label: string, technologies: list<array<string, mixed>>}>
     */
    private function groupByCategory(array $rows): array
    {
        $groups = [];

        foreach (TechnologyCategory::cases() as $category) {
            $inCategory = array_values(array_filter(
                $rows,
                fn (array $row): bool => $row['category'] === $category->value,
            ));

            if ($inCategory === []) {
                continue;
            }

            $groups[] = [
                'category' => $category->value,
                'label' => $category->label(),
                'technologies' => $inCategory,
            ];
        }

        return $groups;
    }

    /**
     * The three counts worth putting above the grid.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  Collection<int, TechnologyUsage>  $usages
     * @return array<string, mixed>
     */
    private function summary(array $rows, $usages): array
    {
        $unused = array_values(array_filter($rows, fn (array $row): bool => $row['total'] === 0));

        // Said not to start anything new with it, still running somewhere: the
        // gap between the recommendation and the estate.
        $heldButRunning = array_values(array_filter(
            $rows,
            fn (array $row): bool => $row['ring'] === 'hold' && $row['total'] > 0,
        ));

        return [
            'technologies' => count($rows),
            'usages' => $usages->count(),
            'unused' => array_map(fn (array $row): array => [
                'name' => $row['name'],
                'url' => $row['url'],
            ], $unused),
            'heldButRunning' => array_map(fn (array $row): array => [
                'name' => $row['name'],
                'url' => $row['url'],
                'total' => $row['total'],
            ], $heldButRunning),
        ];
    }

    /**
     * How a record that carries technologies should be named in a list.
     */
    public static function describe(?Model $record): string
    {
        return match (true) {
            $record instanceof Project => $record->prefix.' — '.$record->name,
            $record === null => 'a record that no longer exists',
            default => (string) ($record->getAttribute('title') ?? 'Untitled'),
        };
    }
}
