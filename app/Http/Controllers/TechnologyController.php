<?php

namespace App\Http\Controllers;

use App\Actions\BuildTechnologyBreakdown;
use App\Actions\RenderTechnologyMarkdown;
use App\Concerns\RendersMarkdown;
use App\Enums\TechnologyCategory;
use App\Enums\TechnologyRing;
use App\Enums\TechnologyStatus;
use App\Http\Requests\Technologies\StoreTechnologyRequest;
use App\Models\Technology;
use App\Models\TechnologyUsage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TechnologyController extends Controller
{
    use RendersMarkdown;

    /**
     * The inventory, narrowable by what it is, what we think of it, and whether
     * it is still running.
     */
    public function index(Request $request): Response
    {
        $category = TechnologyCategory::tryFrom((string) $request->query('category'));
        $ring = TechnologyRing::tryFrom((string) $request->query('ring'));
        $status = TechnologyStatus::tryFrom((string) $request->query('status'));

        return Inertia::render('technologies/index', [
            'technologies' => Technology::query()
                ->withCount('usages')
                ->when($category, fn ($query) => $query->where('category', $category))
                ->when($ring, fn ($query) => $query->where('ring', $ring))
                ->when($status, fn ($query) => $query->where('status', $status))
                ->orderBy('name')
                ->get(),
            'filters' => [
                'category' => $category instanceof TechnologyCategory ? $category->value : '',
                'ring' => $ring instanceof TechnologyRing ? $ring->value : '',
                'status' => $status instanceof TechnologyStatus ? $status->value : '',
            ],
            ...$this->options(),
        ]);
    }

    /**
     * The recap across projects.
     */
    public function breakdown(BuildTechnologyBreakdown $build): Response
    {
        return Inertia::render('technologies/breakdown', $build());
    }

    public function create(): Response
    {
        return Inertia::render('technologies/create', $this->options());
    }

    public function store(StoreTechnologyRequest $request): RedirectResponse
    {
        $technology = Technology::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Technology added.')]);

        return to_route('technologies.show', $technology);
    }

    /**
     * Everywhere this technology is used, grouped by the kind of record.
     */
    public function show(Technology $technology): Response
    {
        $usages = $technology->usages()->with('usable')->get();

        return Inertia::render('technologies/show', [
            'technology' => $technology,
            'markdown' => app(RenderTechnologyMarkdown::class)($technology),
            'html' => ['notes' => $this->renderMarkdown($technology->notes)],
            'usages' => $usages
                ->groupBy('usable_type')
                ->map(fn ($group, string $type): array => [
                    'type' => $type,
                    'label' => $this->carrierLabel($type),
                    'records' => $group
                        ->map(fn (TechnologyUsage $usage): array => [
                            'id' => $usage->id,
                            'label' => BuildTechnologyBreakdown::describe($usage->usable),
                            'url' => $this->carrierUrl($usage),
                            'version' => $usage->version,
                            'role' => $usage->role,
                            'notes' => $usage->notes,
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
            // The spread of versions is the thing an upgrade conversation needs.
            'versions' => $usages
                ->pluck('version')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all(),
        ]);
    }

    public function edit(Technology $technology): Response
    {
        return Inertia::render('technologies/edit', [
            'technology' => $technology,
            ...$this->options(),
        ]);
    }

    public function update(StoreTechnologyRequest $request, Technology $technology): RedirectResponse
    {
        $technology->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Technology updated.')]);

        return to_route('technologies.show', $technology);
    }

    /**
     * Removing a technology takes its usage rows with it, since a usage is only
     * meaningful as a statement about the thing being removed.
     */
    public function destroy(Technology $technology): RedirectResponse
    {
        $technology->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Technology removed.')]);

        return to_route('technologies.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            'categories' => TechnologyCategory::options(),
            'rings' => TechnologyRing::options(),
            'statuses' => TechnologyStatus::options(),
        ];
    }

    private function carrierLabel(string $type): string
    {
        return match ($type) {
            'project' => __('Projects'),
            'prototype' => __('Prototypes'),
            'decision_record' => __('Decision records'),
            'security_note' => __('Security findings'),
            default => $type,
        };
    }

    private function carrierUrl(TechnologyUsage $usage): ?string
    {
        return match ($usage->usable_type) {
            'project' => route('projects.show', $usage->usable_id),
            'prototype' => route('prototypes.show', $usage->usable_id),
            'decision_record' => route('decisions.show', $usage->usable_id),
            'security_note' => route('security-notes.show', $usage->usable_id),
            default => null,
        };
    }
}
