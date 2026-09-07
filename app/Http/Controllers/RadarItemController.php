<?php

namespace App\Http\Controllers;

use App\Actions\PromoteRadarItem;
use App\Concerns\PresentsItemLinks;
use App\Concerns\RendersMarkdown;
use App\Enums\TriageStatus;
use App\Http\Requests\Radar\TriageRadarItemRequest;
use App\Models\FeedSource;
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
     *
     * A busy feed can produce hundreds of items in one fetch, so the queue is
     * paginated and searchable. Search covers the title and the summary, which
     * is what "anything about Postgres" actually means here, and is why there
     * is no topic taxonomy to maintain.
     */
    public function index(Request $request): Response
    {
        $status = TriageStatus::tryFrom((string) $request->query('status'));
        $search = trim((string) $request->query('q'));
        $feedId = (int) $request->query('feed');

        return Inertia::render('radar/index', [
            'items' => RadarItem::query()
                ->with('feedSource:id,name')
                ->withExists(['outgoingItemLinks as promoted' => fn ($query) => $query
                    ->where('target_type', 'vetting_item')])
                ->when($status, fn ($query) => $query->where('triage_status', $status))
                ->when($status === null, fn ($query) => $query->visible())
                ->when($feedId > 0, fn ($query) => $query->where('feed_source_id', $feedId))
                ->when($search !== '', fn ($query) => $query->where(
                    // ponytail: unindexed ILIKE scan. Fine for one person's
                    // reading list; add a tsvector index if it ever drags.
                    fn ($match) => $match
                        ->where('title', 'ilike', '%'.$search.'%')
                        ->orWhere('summary', 'ilike', '%'.$search.'%'),
                ))
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->paginate(25)
                ->withQueryString(),
            'statuses' => TriageStatus::options(),
            'feeds' => FeedSource::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (FeedSource $feed): array => [
                    'value' => (string) $feed->id,
                    'label' => $feed->name,
                ])
                ->all(),
            'filters' => [
                'status' => $status?->value,
                'q' => $search,
                'feed' => $feedId > 0 ? (string) $feedId : '',
            ],
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
     * Raise a vetting item from this radar item and link the two.
     */
    public function promote(RadarItem $radarItem, PromoteRadarItem $promote): RedirectResponse
    {
        if ($promote->alreadyPromoted($radarItem)) {
            Inertia::flash('toast', [
                'type' => 'info',
                'message' => __('That item is already in the vetting log.'),
            ]);

            return back();
        }

        $vettingItem = $promote($radarItem->load('feedSource'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Raised in the vetting log.'),
        ]);

        return to_route('vetting.edit', $vettingItem);
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
