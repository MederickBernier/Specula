<?php

namespace App\Concerns;

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait OffersProjects
{
    /**
     * The projects a record can be filed under, as select options.
     *
     * Every module offers the same list, so the shape lives in one place.
     *
     * @return list<array{value: string, label: string}>
     */
    protected function projectOptions(): array
    {
        return array_values(Project::query()
            ->orderBy('name')
            ->get(['id', 'name', 'prefix', 'archived_at'])
            ->map(fn (Project $project): array => [
                'value' => (string) $project->id,
                // Archived projects stay pickable but say so: work sometimes
                // comes back, and hiding them would silently blank the field
                // on a record already filed under one.
                'label' => $project->prefix.' — '.$project->name
                    .($project->isArchived() ? ' '.__('(archived)') : ''),
            ])
            ->all());
    }

    /**
     * The project filter on a module index: a project id, the string "none"
     * for work filed under no project, or null for everything.
     */
    protected function projectFilter(Request $request): ?string
    {
        $filter = trim((string) $request->query('project'));

        return $filter === '' ? null : $filter;
    }

    /**
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     */
    protected function scopeToProject(Builder $query, ?string $filter): void
    {
        if ($filter === null) {
            return;
        }

        if ($filter === 'none') {
            $query->whereNull('project_id');

            return;
        }

        $query->where('project_id', (int) $filter);
    }

    /**
     * The options a module index offers for narrowing by project.
     *
     * @return list<array{value: string, label: string}>
     */
    protected function projectFilterOptions(): array
    {
        return [
            ['value' => '', 'label' => __('All projects')],
            ['value' => 'none', 'label' => __('No project')],
            ...$this->projectOptions(),
        ];
    }
}
