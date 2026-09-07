<?php

namespace App\Http\Controllers;

use App\Concerns\OffersProjects;
use App\Concerns\PresentsItemLinks;
use App\Concerns\RendersMarkdown;
use App\Enums\ConfidenceLevel;
use App\Enums\PrototypeStatus;
use App\Http\Requests\Prototypes\StorePrototypeRequest;
use App\Http\Requests\Prototypes\UpdatePrototypeRequest;
use App\Models\Prototype;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PrototypeController extends Controller
{
    use OffersProjects;
    use PresentsItemLinks;
    use RendersMarkdown;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $project = $this->projectFilter($request);

        return Inertia::render('prototypes/index', [
            'prototypes' => Prototype::query()
                ->tap(fn ($query) => $this->scopeToProject($query, $project))
                ->orderByDesc('date_started')
                ->orderByDesc('id')
                ->get([
                    'id', 'title', 'status', 'confidence_level',
                    'is_reusable', 'date_started', 'date_completed',
                ]),
            'projectFilters' => $this->projectFilterOptions(),
            'projectFilter' => $project ?? '',
            ...$this->formOptions(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('prototypes/create', $this->formOptions());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePrototypeRequest $request): RedirectResponse
    {
        $prototype = Prototype::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Prototype created.')]);

        return to_route('prototypes.show', $prototype);
    }

    /**
     * Display the specified resource.
     */
    public function show(Prototype $prototype): Response
    {
        return Inertia::render('prototypes/show', [
            'prototype' => $prototype,
            ...$this->itemLinkProps($prototype),
            'html' => [
                'hypothesis' => $this->renderMarkdown($prototype->hypothesis),
                'test_approach' => $this->renderMarkdown($prototype->test_approach),
                'result' => $this->renderMarkdown($prototype->result),
                'abandoned_reason' => $this->renderMarkdown($prototype->abandoned_reason),
                'reusability_note' => $this->renderMarkdown($prototype->reusability_note),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Prototype $prototype): Response
    {
        return Inertia::render('prototypes/edit', [
            'prototype' => $prototype,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePrototypeRequest $request, Prototype $prototype): RedirectResponse
    {
        $prototype->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Prototype updated.')]);

        return to_route('prototypes.show', $prototype);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Prototype $prototype): RedirectResponse
    {
        $prototype->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Prototype deleted.')]);

        return to_route('prototypes.index');
    }

    /**
     * The select options both the create and edit forms need.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'projects' => $this->projectOptions(),
            'statuses' => PrototypeStatus::options(),
            'confidenceLevels' => ConfidenceLevel::options(),
        ];
    }
}
