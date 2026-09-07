<?php

namespace App\Http\Controllers;

use App\Concerns\PresentsItemLinks;
use App\Concerns\RendersMarkdown;
use App\Enums\TriageStatus;
use App\Http\Requests\Radar\TriageRadarItemRequest;
use App\Models\RadarItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RadarItemController extends Controller
{
    use PresentsItemLinks;
    use RendersMarkdown;

    /**
     * Display the triage queue.
     *
     * Discarded items are hidden by default rather than deleted, so a
     * too-hasty dismissal can still be found and undone.
     */
    public function index(Request $request): Response
    {
        $status = TriageStatus::tryFrom((string) $request->query('status'));

        return Inertia::render('radar/index', [
            'items' => RadarItem::query()
                ->with('feedSource:id,name')
                ->when($status, fn ($query) => $query->where('triage_status', $status))
                ->when($status === null, fn ($query) => $query->visible())
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->get(),
            'statuses' => TriageStatus::options(),
            'filter' => $status?->value,
            'pendingCount' => RadarItem::query()->where('triage_status', TriageStatus::Pending)->count(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(RadarItem $radarItem): Response
    {
        return Inertia::render('radar/show', [
            'item' => $radarItem->load('feedSource:id,name'),
            'html' => [
                'relevance_note' => $this->renderMarkdown($radarItem->relevance_note),
            ],
            'statuses' => TriageStatus::options(),
            ...$this->itemLinkProps($radarItem),
        ]);
    }

    /**
     * Triage the item: keep it with a note, or dismiss it.
     */
    public function triage(TriageRadarItemRequest $request, RadarItem $radarItem): RedirectResponse
    {
        $radarItem->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Item triaged.')]);

        return back();
    }
}
