<?php

namespace App\Concerns;

use App\Models\Project;

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
            ->get(['id', 'name', 'prefix'])
            ->map(fn (Project $project): array => [
                'value' => (string) $project->id,
                'label' => $project->prefix.' — '.$project->name,
            ])
            ->all());
    }
}
