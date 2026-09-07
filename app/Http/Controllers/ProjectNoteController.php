<?php

namespace App\Http\Controllers;

use App\Http\Requests\Projects\StoreProjectNoteRequest;
use App\Models\Project;
use App\Models\ProjectNote;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ProjectNoteController extends Controller
{
    /**
     * Add a note to a project.
     */
    public function store(StoreProjectNoteRequest $request, Project $project): RedirectResponse
    {
        $project->notes()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note added.')]);

        return back();
    }

    /**
     * Rewrite a note.
     */
    public function update(StoreProjectNoteRequest $request, ProjectNote $projectNote): RedirectResponse
    {
        $projectNote->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note updated.')]);

        return back();
    }

    /**
     * Remove a note.
     */
    public function destroy(ProjectNote $projectNote): RedirectResponse
    {
        $projectNote->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note removed.')]);

        return back();
    }
}
