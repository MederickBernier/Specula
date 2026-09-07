<?php

use App\Models\FeedSource;
use App\Models\RadarItem;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

function feedBody(string $link = 'https://example.test/one'): string
{
    return '<rss version="2.0"><channel><item><title>New thing</title><link>'
        .$link.'</link></item></channel></rss>';
}

test('guests cannot start a scan', function () {
    auth()->logout();

    $this->post(route('radar.feeds.fetch-all'))->assertRedirect(route('login'));
});

test('one button scans every active feed', function () {
    Http::fake([
        'a.test/*' => Http::response(feedBody('https://example.test/a')),
        'b.test/*' => Http::response(feedBody('https://example.test/b')),
    ]);

    FeedSource::factory()->create(['url' => 'https://a.test/feed.xml']);
    FeedSource::factory()->create(['url' => 'https://b.test/feed.xml']);
    FeedSource::factory()->inactive()->create(['url' => 'https://c.test/feed.xml']);

    $this->from(route('radar.feeds.index'))
        ->post(route('radar.feeds.fetch-all'))
        ->assertRedirect(route('radar.feeds.index'));

    expect(RadarItem::count())->toBe(2);

    // The paused source was left alone, so it still has no scan time.
    expect(FeedSource::where('url', 'https://c.test/feed.xml')->sole()->last_fetched_at)
        ->toBeNull();
});

test('one feed failing does not stop the others', function () {
    Http::fake([
        'good.test/*' => Http::response(feedBody('https://example.test/good')),
        'bad.test/*' => Http::response('nope', 500),
    ]);

    FeedSource::factory()->create(['url' => 'https://good.test/feed.xml']);
    $bad = FeedSource::factory()->create(['url' => 'https://bad.test/feed.xml']);

    $this->post(route('radar.feeds.fetch-all'))->assertRedirect();

    expect(RadarItem::count())->toBe(1)
        ->and($bad->refresh()->last_error)->not->toBeNull();
});

test('scanning with nothing active says so rather than looking broken', function () {
    FeedSource::factory()->inactive()->create();

    $this->post(route('radar.feeds.fetch-all'))->assertRedirect();

    expect(RadarItem::count())->toBe(0);
});

test('the feeds page says when the last scan happened', function () {
    Http::fake(['*' => Http::response(feedBody())]);

    FeedSource::factory()->create();

    $this->get(route('radar.feeds.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('lastScanAt', null)
            // never scanned counts as overdue: nothing is watching yet
            ->where('scanOverdue', true));

    $this->post(route('radar.feeds.fetch-all'));

    $this->get(route('radar.feeds.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('lastScanAt', fn (?string $at) => $at !== null)
            ->where('scanOverdue', false));
});

test('a scan that has not run for hours is called overdue', function () {
    FeedSource::factory()->create(['last_fetched_at' => now()->subHours(5)]);

    $this->get(route('radar.feeds.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('scanOverdue', true));
});

test('with no feeds at all there is nothing overdue', function () {
    $this->get(route('radar.feeds.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('scanOverdue', false));
});

test('the radar queue also carries the last scan time', function () {
    FeedSource::factory()->create(['last_fetched_at' => now()->subMinutes(10)]);

    $this->get(route('radar.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('lastScanAt', fn (?string $at) => $at !== null));
});

test('a read-only account cannot start a scan', function () {
    auth()->logout();
    $this->actingAs(User::factory()->readOnly()->create());

    FeedSource::factory()->create();

    $this->post(route('radar.feeds.fetch-all'))->assertForbidden();

    expect(RadarItem::count())->toBe(0);
});
