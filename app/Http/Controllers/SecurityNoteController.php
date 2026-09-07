<?php

namespace App\Http\Controllers;

use App\Concerns\OffersProjects;
use App\Concerns\PresentsItemLinks;
use App\Concerns\RendersMarkdown;
use App\Enums\SecurityNoteSource;
use App\Enums\SecurityNoteStatus;
use App\Enums\SecurityRoutedTo;
use App\Enums\SecuritySeverity;
use App\Http\Requests\Security\StoreSecurityNoteRequest;
use App\Http\Requests\Security\UpdateSecurityNoteRequest;
use App\Models\SecurityNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecurityNoteController extends Controller
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

        return Inertia::render('security/index', [
            'notes' => SecurityNote::query()
                ->tap(fn ($query) => $this->scopeToProject($query, $project))
                ->orderByDesc('date_flagged')
                ->orderByDesc('id')
                ->get([
                    'id', 'title', 'source', 'category', 'severity',
                    'is_issue', 'routed_to', 'status', 'date_flagged', 'date_resolved',
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
        return Inertia::render('security/create', $this->formOptions());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSecurityNoteRequest $request): RedirectResponse
    {
        $note = SecurityNote::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Security note created.')]);

        return to_route('security-notes.show', $note);
    }

    /**
     * Display the specified resource.
     */
    public function show(SecurityNote $securityNote): Response
    {
        return Inertia::render('security/show', [
            'note' => $securityNote,
            ...$this->itemLinkProps($securityNote),
            'html' => [
                'finding' => $this->renderMarkdown($securityNote->finding),
                'non_issue_reason' => $this->renderMarkdown($securityNote->non_issue_reason),
                'deferral_reason' => $this->renderMarkdown($securityNote->deferral_reason),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SecurityNote $securityNote): Response
    {
        return Inertia::render('security/edit', [
            'note' => $securityNote,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSecurityNoteRequest $request, SecurityNote $securityNote): RedirectResponse
    {
        $securityNote->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Security note updated.')]);

        return to_route('security-notes.show', $securityNote);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SecurityNote $securityNote): RedirectResponse
    {
        $securityNote->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Security note deleted.')]);

        return to_route('security-notes.index');
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
            'sources' => SecurityNoteSource::options(),
            'severities' => SecuritySeverity::options(),
            'routes' => SecurityRoutedTo::options(),
            'statuses' => SecurityNoteStatus::options(),
        ];
    }
}
