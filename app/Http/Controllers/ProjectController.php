<?php

namespace App\Http\Controllers;

use App\Actions\BuildProjectTimeline;
use App\Actions\RenderProjectMarkdown;
use App\Concerns\PresentsTechnologyStack;
use App\Concerns\RendersMarkdown;
use App\Http\Requests\Projects\StoreProjectRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ProjectController extends Controller
{
    use PresentsTechnologyStack;
    use RendersMarkdown;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $showArchived = $request->boolean('archived');

        return Inertia::render('projects/index', [
            'projects' => Project::query()
                ->withCount(['decisionRecords', 'vettingItems', 'prototypes', 'securityNotes', 'notes'])
                ->when($showArchived, fn ($query) => $query->archived())
                ->when(! $showArchived, fn ($query) => $query->active())
                ->orderBy('name')
                ->get(),
            'showingArchived' => $showArchived,
            'archivedCount' => Project::query()->archived()->count(),
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
            ...$this->technologyStackProps($project),
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
     * The project's story in one order, newest first.
     */
    public function timeline(Project $project, BuildProjectTimeline $build): Response
    {
        return Inertia::render('projects/timeline', [
            'project' => $project,
            'events' => $build($project),
        ]);
    }

    /**
     * Download the project as one markdown document.
     */
    public function export(Project $project, RenderProjectMarkdown $render): HttpResponse
    {
        return response($render($project), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$render->filename($project).'"',
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
     * Put a finished project away, or bring it back.
     *
     * Archiving only tidies the list. The records filed under the project keep
     * their link to it, and it can still be picked in a form, marked as
     * archived, since work occasionally comes back from the dead.
     */
    public function archive(Request $request, Project $project): RedirectResponse
    {
        $archived = $request->boolean('archived');

        $project->forceFill(['archived_at' => $archived ? now() : null])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $archived ? __('Project archived.') : __('Project restored.'),
        ]);

        return back();
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
