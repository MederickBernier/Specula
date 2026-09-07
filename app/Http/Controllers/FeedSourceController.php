<?php

namespace App\Http\Controllers;

use App\Actions\FetchFeedSource;
use App\Enums\FeedType;
use App\Http\Requests\Radar\StoreFeedSourceRequest;
use App\Models\FeedSource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FeedSourceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render('radar/feeds/index', [
            'feeds' => FeedSource::query()
                ->withCount('radarItems')
                ->orderBy('name')
                ->get(),
            'feedTypes' => FeedType::options(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFeedSourceRequest $request): RedirectResponse
    {
        FeedSource::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Feed source added.')]);

        return to_route('radar.feeds.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreFeedSourceRequest $request, FeedSource $feedSource): RedirectResponse
    {
        $feedSource->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Feed source updated.')]);

        return to_route('radar.feeds.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * Items already pulled from the feed survive it: the foreign key is nulled
     * rather than cascading, so triage work is not lost with the source.
     */
    public function destroy(FeedSource $feedSource): RedirectResponse
    {
        $feedSource->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Feed source removed.')]);

        return to_route('radar.feeds.index');
    }

    /**
     * Fetch one source now instead of waiting for the schedule.
     */
    public function fetch(FeedSource $feedSource, FetchFeedSource $fetch): RedirectResponse
    {
        $stored = $fetch($feedSource);

        Inertia::flash('toast', $feedSource->last_error === null
            ? ['type' => 'success', 'message' => trans_choice(':count new item|:count new items', $stored)]
            : ['type' => 'error', 'message' => $feedSource->last_error]);

        return back();
    }
}
