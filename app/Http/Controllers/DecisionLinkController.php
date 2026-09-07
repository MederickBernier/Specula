<?php

namespace App\Http\Controllers;

use App\Http\Requests\Decisions\StoreDecisionLinkRequest;
use App\Models\DecisionLink;
use App\Models\DecisionRecord;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class DecisionLinkController extends Controller
{
    /**
     * Link the given decision record to another one.
     */
    public function store(StoreDecisionLinkRequest $request, DecisionRecord $decisionRecord): RedirectResponse
    {
        $decisionRecord->outgoingLinks()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Link added.')]);

        return to_route('decisions.show', $decisionRecord);
    }

    /**
     * Remove a link between two decision records.
     */
    public function destroy(DecisionLink $decisionLink): RedirectResponse
    {
        $sourceId = $decisionLink->source_id;

        $decisionLink->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Link removed.')]);

        return to_route('decisions.show', $sourceId);
    }
}
