<?php

namespace App\Actions;

use App\Models\DecisionRecord;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\Prototype;
use App\Models\RadarItem;
use App\Models\SecurityNote;
use App\Models\VettingItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * One search over everything written down.
 *
 * The point of modelling a decision's options as rows rather than a text blob
 * was to be able to ask later whether a thing had already been weighed
 * somewhere, so the options are searched alongside the records that hold them.
 */
class SearchEverything
{
    /**
     * How many hits to show per module before saying "and N more".
     */
    private const PER_MODULE = 8;

    /**
     * @return list<array{
     *     module: string,
     *     total: int,
     *     results: list<array{label: string, url: string, snippet: string|null, meta: string|null}>
     * }>
     */
    public function __invoke(string $term): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $groups = [];

        foreach ($this->sources() as $source) {
            $group = $this->search($source, $term);

            if ($group['total'] > 0) {
                $groups[] = $group;
            }
        }

        return $groups;
    }

    /**
     * Each module, the fields worth searching, and how to present a hit.
     *
     * @return list<array<string, mixed>>
     */
    private function sources(): array
    {
        return [
            [
                'module' => __('Projects'),
                'model' => Project::class,
                'columns' => ['name', 'prefix', 'description'],
                'present' => fn (Project $project): array => [
                    'label' => $project->prefix.' — '.$project->name,
                    'url' => route('projects.show', $project),
                    'meta' => $project->isArchived() ? __('Archived') : null,
                ],
            ],
            [
                'module' => __('Decision records'),
                'model' => DecisionRecord::class,
                'columns' => [
                    'title', 'project_prefix', 'category', 'author', 'deciders', 'affects',
                    'proposal_context', 'recommendation', 'consequences', 'conditions_for_revisiting',
                ],
                // A decision also matches on the options weighed under it.
                'relations' => ['options' => ['name', 'description', 'pros', 'cons']],
                'present' => fn (DecisionRecord $record): array => [
                    'label' => $record->linkLabel(),
                    'url' => $record->linkUrl(),
                    'meta' => $record->status->label(),
                ],
            ],
            [
                'module' => __('Vetting log'),
                'model' => VettingItem::class,
                'columns' => [
                    'title', 'source_detail', 'proposal_description', 'assessment', 'rejection_reason',
                ],
                'present' => fn (VettingItem $item): array => [
                    'label' => $item->linkLabel(),
                    'url' => $item->linkUrl(),
                    'meta' => $item->status->label(),
                ],
            ],
            [
                'module' => __('Prototypes'),
                'model' => Prototype::class,
                'columns' => [
                    'title', 'hypothesis', 'test_approach', 'result',
                    'abandoned_reason', 'reusability_note', 'repo_reference',
                ],
                'present' => fn (Prototype $prototype): array => [
                    'label' => $prototype->linkLabel(),
                    'url' => $prototype->linkUrl(),
                    'meta' => $prototype->status->label(),
                ],
            ],
            [
                'module' => __('Security posture'),
                'model' => SecurityNote::class,
                'columns' => [
                    'title', 'category', 'finding', 'non_issue_reason', 'deferral_reason',
                ],
                'present' => fn (SecurityNote $note): array => [
                    'label' => $note->linkLabel(),
                    'url' => $note->linkUrl(),
                    'meta' => $note->severity->label().', '.$note->status->label(),
                ],
            ],
            [
                'module' => __('Project notes'),
                'model' => ProjectNote::class,
                'columns' => ['title', 'body'],
                'present' => fn (ProjectNote $note): array => [
                    'label' => $note->title,
                    'url' => route('projects.show', $note->project_id),
                    'meta' => null,
                ],
            ],
            [
                'module' => __('Tech radar'),
                // The only source still searchable in SQL: radar items hold
                // public feed text and are deliberately not encrypted.
                'sql' => true,
                'model' => RadarItem::class,
                'columns' => ['title', 'summary', 'relevance_note'],
                'present' => fn (RadarItem $item): array => [
                    'label' => $item->linkLabel(),
                    'url' => $item->linkUrl(),
                    'meta' => $item->triage_status->label(),
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array{
     *     module: string,
     *     total: int,
     *     results: list<array{label: string, url: string, snippet: string|null, meta: string|null}>
     * }
     */
    private function search(array $source, string $term): array
    {
        /** @var class-string<Model> $class */
        $class = $source['model'];
        /** @var list<string> $columns */
        $columns = $source['columns'];
        /** @var array<string, list<string>> $relations */
        $relations = $source['relations'] ?? [];

        $matches = ($source['sql'] ?? false) === true
            ? $this->matchInDatabase($class, $columns, $relations, $term)
            : $this->matchInMemory($class, $columns, $relations, $term);

        $total = $matches->count();

        $results = [];

        foreach ($matches->take(self::PER_MODULE) as $record) {
            /** @var callable(Model): array{label: string, url: string, meta: string|null} $present */
            $present = $source['present'];
            $presented = $present($record);

            $results[] = [
                'label' => $presented['label'],
                'url' => $presented['url'],
                'meta' => $presented['meta'],
                'snippet' => $this->snippet($record, $columns, $term),
            ];
        }

        return [
            'module' => $source['module'],
            'total' => $total,
            'results' => $results,
        ];
    }

    /**
     * @param  class-string<Model>  $class
     * @param  list<string>  $columns
     * @param  array<string, list<string>>  $relations
     * @return Collection<int, Model>
     */
    private function matchInDatabase(string $class, array $columns, array $relations, string $term)
    {
        return $class::query()
            ->where(function (Builder $match) use ($columns, $relations, $term): void {
                foreach ($columns as $column) {
                    $match->orWhere($column, 'ilike', '%'.$term.'%');
                }

                foreach ($relations as $relation => $relationColumns) {
                    $match->orWhereHas($relation, function (Builder $related) use ($relationColumns, $term): void {
                        $related->where(function (Builder $inner) use ($relationColumns, $term): void {
                            foreach ($relationColumns as $column) {
                                $inner->orWhere($column, 'ilike', '%'.$term.'%');
                            }
                        });
                    });
                }
            })
            ->latest('updated_at')
            ->get();
    }

    /**
     * Matching after decryption, for everything the database can no longer read.
     *
     * The work modules are encrypted at rest, so their text is opaque to SQL:
     * a LIKE against ciphertext matches nothing, quietly. Rows are read and
     * compared here instead.
     *
     * ponytail: reads the table to search it. Fine for a few hundred records
     * one person wrote; if it ever drags, the answer is a blind index of
     * hashed tokens rather than giving the plain text back to the database.
     *
     * @param  class-string<Model>  $class
     * @param  list<string>  $columns
     * @param  array<string, list<string>>  $relations
     * @return Collection<int, Model>
     */
    private function matchInMemory(string $class, array $columns, array $relations, string $term)
    {
        return $class::query()
            ->with(array_keys($relations))
            ->latest('updated_at')
            ->get()
            ->filter(function (Model $record) use ($columns, $relations, $term): bool {
                foreach ($columns as $column) {
                    if ($this->contains($record->getAttribute($column), $term)) {
                        return true;
                    }
                }

                foreach ($relations as $relation => $relationColumns) {
                    foreach ($record->getRelation($relation) as $related) {
                        foreach ($relationColumns as $column) {
                            if ($this->contains($related->getAttribute($column), $term)) {
                                return true;
                            }
                        }
                    }
                }

                return false;
            })
            ->values();
    }

    private function contains(mixed $value, string $term): bool
    {
        return is_string($value) && mb_stripos($value, $term) !== false;
    }

    /**
     * A short window of the field the term was found in, so a hit shows why it
     * is a hit rather than only that it is one.
     *
     * @param  list<string>  $columns
     */
    private function snippet(Model $record, array $columns, string $term): ?string
    {
        foreach ($columns as $column) {
            $value = trim((string) ($record->getAttribute($column) ?? ''));

            if ($value === '') {
                continue;
            }

            $position = mb_stripos($value, $term);

            if ($position === false) {
                continue;
            }

            $start = max(0, $position - 60);
            $window = mb_substr($value, $start, 200);
            $window = (string) preg_replace('/\s+/u', ' ', $window);

            return ($start > 0 ? '…' : '').trim($window).'…';
        }

        return null;
    }
}
