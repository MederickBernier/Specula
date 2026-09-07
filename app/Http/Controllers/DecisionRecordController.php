<?php

namespace App\Http\Controllers;

use App\Actions\RenderDecisionRecordMarkdown;
use App\Concerns\OffersProjects;
use App\Concerns\PresentsItemLinks;
use App\Concerns\RendersMarkdown;
use App\Enums\DecisionRelationshipType;
use App\Enums\DecisionStatus;
use App\Http\Requests\Decisions\StoreDecisionRecordRequest;
use App\Http\Requests\Decisions\UpdateDecisionRecordRequest;
use App\Models\DecisionRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DecisionRecordController extends Controller
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

        return Inertia::render('decisions/index', [
            'records' => DecisionRecord::query()
                ->tap(fn ($query) => $this->scopeToProject($query, $project))
                ->orderBy('project_prefix')
                ->orderBy('category')
                ->orderBy('sequence')
                ->get(['id', 'project_prefix', 'category', 'sequence', 'title', 'status', 'updated_at']),
            'projectFilters' => $this->projectFilterOptions(),
            'projectFilter' => $project ?? '',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('decisions/create', [
            'statuses' => DecisionStatus::options(),
            'projects' => $this->projectOptions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDecisionRecordRequest $request): RedirectResponse
    {
        $record = DB::transaction(function () use ($request): DecisionRecord {
            $record = DecisionRecord::create($request->safe()->except('options'));

            $record->options()->createMany($request->validated('options', []));

            return $record;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Decision record created.')]);

        return to_route('decisions.show', $record);
    }

    /**
     * Display the specified resource.
     */
    public function show(DecisionRecord $decisionRecord): Response
    {
        $decisionRecord->load([
            'options',
            'outgoingLinks.target',
            'incomingLinks.source',
        ]);

        return Inertia::render('decisions/show', [
            'record' => $decisionRecord,
            // Rendered here so the copy button needs no round trip; the
            // download route renders the same document.
            'markdown' => app(RenderDecisionRecordMarkdown::class)($decisionRecord),
            ...$this->itemLinkProps($decisionRecord),
            'html' => $this->renderRecordMarkdown($decisionRecord),
            'relationshipTypes' => DecisionRelationshipType::options(),
            'linkTargets' => DecisionRecord::query()
                ->whereKeyNot($decisionRecord->getKey())
                ->orderBy('project_prefix')
                ->orderBy('category')
                ->orderBy('sequence')
                ->get(['id', 'project_prefix', 'category', 'sequence', 'title']),
        ]);
    }

    /**
     * Download the record as the markdown document it was written as.
     */
    public function export(DecisionRecord $decisionRecord, RenderDecisionRecordMarkdown $render): HttpResponse
    {
        return response($render($decisionRecord), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$render->filename($decisionRecord).'"',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DecisionRecord $decisionRecord): Response
    {
        return Inertia::render('decisions/edit', [
            'record' => $decisionRecord->load('options'),
            'statuses' => DecisionStatus::options(),
            'projects' => $this->projectOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDecisionRecordRequest $request, DecisionRecord $decisionRecord): RedirectResponse
    {
        DB::transaction(function () use ($request, $decisionRecord): void {
            $decisionRecord->update($request->safe()->except('options'));

            // ponytail: options are replaced wholesale rather than diffed by id.
            // Move to a keyed sync only if option ids need to stay stable.
            $decisionRecord->options()->delete();
            $decisionRecord->options()->createMany($request->validated('options', []));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Decision record updated.')]);

        return to_route('decisions.show', $decisionRecord);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DecisionRecord $decisionRecord): RedirectResponse
    {
        $decisionRecord->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Decision record deleted.')]);

        return to_route('decisions.index');
    }

    /**
     * The record's markdown sections rendered to HTML for reading.
     *
     * @return array<string, mixed>
     */
    private function renderRecordMarkdown(DecisionRecord $record): array
    {
        return [
            'proposal_context' => $this->renderMarkdown($record->proposal_context),
            'recommendation' => $this->renderMarkdown($record->recommendation),
            'consequences' => $this->renderMarkdown($record->consequences),
            'conditions_for_revisiting' => $this->renderMarkdown($record->conditions_for_revisiting),
            'options' => $record->options
                ->mapWithKeys(fn ($option): array => [$option->id => [
                    'description' => $this->renderMarkdown($option->description),
                    'pros' => $this->renderMarkdown($option->pros),
                    'cons' => $this->renderMarkdown($option->cons),
                ]])
                ->all(),
            'links' => $record->outgoingLinks->merge($record->incomingLinks)
                ->mapWithKeys(fn ($link): array => [
                    $link->id => $this->renderMarkdown($link->impact_summary),
                ])
                ->all(),
        ];
    }
}
