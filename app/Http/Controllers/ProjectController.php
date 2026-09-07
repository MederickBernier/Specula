<?php

namespace App\Http\Controllers;

use App\Concerns\RendersMarkdown;
use App\Http\Requests\Projects\StoreProjectRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    use RendersMarkdown;

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render('projects/index', [
            'projects' => Project::query()
                ->withCount(['decisionRecords', 'vettingItems', 'prototypes', 'securityNotes', 'notes'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('projects/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = Project::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project created.')]);

        return to_route('projects.show', $project);
    }

    /**
     * Everything filed under this project, in one place.
     */
    public function show(Project $project): Response
    {
        return Inertia::render('projects/show', [
            'project' => $project,
            'html' => ['description' => $this->renderMarkdown($project->description)],
            'decisions' => $project->decisionRecords()
                ->orderBy('category')
                ->orderBy('sequence')
                ->get(['id', 'project_prefix', 'category', 'sequence', 'title', 'status']),
            'vettingItems' => $project->vettingItems()
                ->orderByDesc('date_raised')
                ->get(['id', 'title', 'status', 'date_raised', 'date_resolved']),
            'prototypes' => $project->prototypes()
                ->orderByDesc('date_started')
                ->get(['id', 'title', 'status', 'date_started', 'date_completed']),
            'securityNotes' => $project->securityNotes()
                ->orderByDesc('date_flagged')
                ->get(['id', 'title', 'severity', 'status', 'date_flagged']),
            'notes' => $project->notes()
                ->latest()
                ->get()
                ->map(fn ($note): array => [
                    'id' => $note->id,
                    'title' => $note->title,
                    'body' => $note->body,
                    'html' => $this->renderMarkdown($note->body),
                    'updated_at' => $note->updated_at,
                ])
                ->all(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project): Response
    {
        return Inertia::render('projects/edit', ['project' => $project]);
    }

    /**
     * Update the specified resource in storage.
     *
     * Changing the prefix re-stamps the document id of every decision filed
     * under the project, which is the point of deriving it.
     */
    public function update(StoreProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        $project->decisionRecords()->update(['project_prefix' => $project->prefix]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project updated.')]);

        return to_route('projects.show', $project);
    }

    /**
     * Remove the specified resource from storage.
     *
     * The work filed under a project outlives it: the foreign keys are nulled
     * rather than cascaded, so only the project and its own notes go.
     */
    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project removed.')]);

        return to_route('projects.index');
    }
}
