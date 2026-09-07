<?php

namespace App\Http\Controllers;

use App\Actions\FetchFeedSource;
use App\Enums\FeedType;
use App\Http\Requests\Radar\StoreFeedSourceRequest;
use App\Models\FeedSource;
use Carbon\CarbonImmutable;
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
            ...$this->scanStatus(),
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
     * Scan every active source now instead of waiting for the schedule.
     *
     * ponytail: fetched in the request, one after another. Ten feeds answer in
     * a few seconds; if the list ever grows enough to bump the request timeout,
     * this becomes a queued job and the button reports back rather than waits.
     */
    public function fetchAll(FetchFeedSource $fetch): RedirectResponse
    {
        $sources = FeedSource::query()->where('is_active', true)->get();

        if ($sources->isEmpty()) {
            Inertia::flash('toast', [
                'type' => 'info',
                'message' => __('No active feed sources to scan.'),
            ]);

            return back();
        }

        $stored = 0;
        $failed = 0;

        foreach ($sources as $source) {
            $stored += $fetch($source);
            $failed += $source->last_error === null ? 0 : 1;
        }

        Inertia::flash('toast', $failed === 0
            ? ['type' => 'success', 'message' => trans_choice(
                'Scanned :feeds feeds, :count new item|Scanned :feeds feeds, :count new items',
                $stored,
                ['feeds' => $sources->count()],
            )]
            : ['type' => 'warning', 'message' => trans_choice(
                ':count new item, but :failed feed did not answer|:count new items, but :failed feeds did not answer',
                $stored,
                ['failed' => $failed],
            )]);

        return back();
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

    /**
     * When the feeds were last scanned, and whether that was long enough ago to
     * suggest nothing is scanning them.
     *
     * The schedule only runs if something is running the scheduler, which is
     * easy to forget and silent when forgotten, so the page says so rather than
     * leaving a quiet radar to look like a quiet week.
     *
     * @return array<string, mixed>
     */
    private function scanStatus(): array
    {
        $active = FeedSource::query()->where('is_active', true);
        $lastScan = (clone $active)->max('last_fetched_at');
        $lastScan = $lastScan === null ? null : CarbonImmutable::parse($lastScan);

        return [
            'lastScanAt' => $lastScan?->toIso8601String(),
            // Scheduled hourly, so a gap of several hours means the scheduler is
            // not running rather than that the feeds are quiet.
            'scanOverdue' => (clone $active)->exists()
                && ($lastScan === null || $lastScan->lt(CarbonImmutable::now()->subHours(3))),
        ];
    }
}
