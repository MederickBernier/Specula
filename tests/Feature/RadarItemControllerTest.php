<?php

use App\Enums\TriageStatus;
use App\Models\FeedSource;
use App\Models\RadarItem;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('guests cannot reach the radar', function () {
    auth()->logout();

    $this->get(route('radar.index'))->assertRedirect(route('login'));
    $this->get(route('radar.feeds.index'))->assertRedirect(route('login'));
});

test('the queue hides discarded items by default', function () {
    RadarItem::factory()->count(2)->create();
    RadarItem::factory()->relevant()->create();
    RadarItem::factory()->discarded()->create();

    $this->get(route('radar.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('radar/index')
            ->has('items.data', 3)
            ->where('pendingCount', 2)
            ->where('filters.status', null));
});

test('discarded items can still be found on purpose', function () {
    RadarItem::factory()->create();
    RadarItem::factory()->discarded()->create();

    $this->get(route('radar.index', ['status' => TriageStatus::Discarded->value]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('items.data', 1)
            ->where('filters.status', 'discarded'));
});

test('marking an item relevant records the note and the triage date', function () {
    $item = RadarItem::factory()->create();

    $this->from(route('radar.index'))
        ->patch(route('radar.triage', $item), [
            'triage_status' => TriageStatus::Relevant->value,
            'relevance_note' => 'Worth a vetting item.',
        ])->assertRedirect(route('radar.index'));

    $item->refresh();

    expect($item->triage_status)->toBe(TriageStatus::Relevant)
        ->and($item->relevance_note)->toBe('Worth a vetting item.')
        ->and($item->triaged_at)->not->toBeNull()
        ->and($item->is_hidden)->toBeFalse();
});

test('keeping an item needs a reason', function () {
    $item = RadarItem::factory()->create();

    $this->patch(route('radar.triage', $item), [
        'triage_status' => TriageStatus::Relevant->value,
    ])->assertSessionHasErrors('relevance_note');

    expect($item->refresh()->triage_status)->toBe(TriageStatus::Pending);
});

test('discarding hides the item without deleting it', function () {
    $item = RadarItem::factory()->create();

    $this->patch(route('radar.triage', $item), [
        'triage_status' => TriageStatus::Discarded->value,
    ])->assertSessionHasNoErrors();

    $item->refresh();

    expect($item->is_hidden)->toBeTrue()
        ->and($item->triaged_at)->not->toBeNull()
        ->and(RadarItem::count())->toBe(1);
});

test('putting an item back in the queue clears its triage', function () {
    $item = RadarItem::factory()->discarded()->create();

    $this->patch(route('radar.triage', $item), [
        'triage_status' => TriageStatus::Pending->value,
    ]);

    $item->refresh();

    expect($item->is_hidden)->toBeFalse()
        ->and($item->triaged_at)->toBeNull();
});

test('a radar item can be linked into the other modules', function () {
    $item = RadarItem::factory()->relevant()->create();

    $this->get(route('radar.show', $item))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('radar/show')
            ->where('itemLinkSource.type', 'radar_item')
            ->where('html.relevance_note', fn (?string $html) => $html !== null));

    expect($item->linkUrl())->toBe(route('radar.show', $item))
        ->and(RadarItem::moduleLabel())->toBe('Radar item');
});

test('feed sources can be added, fetched, edited and removed', function () {
    Http::fake(['*' => Http::response(
        '<rss version="2.0"><channel><item><title>New</title><link>https://example.test/a</link></item></channel></rss>'
    )]);

    $this->post(route('radar.feeds.store'), [
        'name' => 'Example blog',
        'url' => 'https://example.test/feed.xml',
        'feed_type' => 'rss',
        'is_active' => true,
    ])->assertRedirect(route('radar.feeds.index'));

    $source = FeedSource::sole();

    $this->post(route('radar.feeds.fetch', $source))->assertRedirect();
    expect(RadarItem::count())->toBe(1);

    $this->put(route('radar.feeds.update', $source), [
        'name' => 'Renamed',
        'url' => $source->url,
        'feed_type' => 'atom',
        'is_active' => false,
    ])->assertRedirect(route('radar.feeds.index'));

    expect($source->refresh()->name)->toBe('Renamed')
        ->and($source->is_active)->toBeFalse();

    $this->delete(route('radar.feeds.destroy', $source))->assertRedirect(route('radar.feeds.index'));

    expect(FeedSource::count())->toBe(0)
        ->and(RadarItem::count())->toBe(1)
        ->and(RadarItem::sole()->feed_source_id)->toBeNull();
});

test('a feed url must be http or https', function () {
    $this->post(route('radar.feeds.store'), [
        'name' => 'Local file',
        'url' => 'file:///etc/passwd',
        'feed_type' => 'rss',
        'is_active' => true,
    ])->assertSessionHasErrors('url');

    expect(FeedSource::count())->toBe(0);
});

test('a feed url cannot be added twice', function () {
    FeedSource::factory()->create(['url' => 'https://example.test/feed.xml']);

    $this->post(route('radar.feeds.store'), [
        'name' => 'Duplicate',
        'url' => 'https://example.test/feed.xml',
        'feed_type' => 'rss',
        'is_active' => true,
    ])->assertSessionHasErrors('url');
});

test('the queue is paginated', function () {
    RadarItem::factory()->count(30)->create();

    $this->get(route('radar.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('items.data', 25)
            ->where('items.total', 30)
            ->where('items.current_page', 1)
            ->where('items.last_page', 2));

    $this->get(route('radar.index', ['page' => 2]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('items.data', 5)
            ->where('items.current_page', 2));
});

test('search matches the title and the summary, case-insensitively', function () {
    RadarItem::factory()->create(['title' => 'Postgres 18 lands', 'summary' => null]);
    RadarItem::factory()->create(['title' => 'Unrelated', 'summary' => 'Mentions POSTGRES once.']);
    RadarItem::factory()->create(['title' => 'Nothing to see', 'summary' => 'About queues.']);

    $this->get(route('radar.index', ['q' => 'postgres']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('items.data', 2)
            ->where('filters.q', 'postgres'));
});

test('the queue can be narrowed to one feed', function () {
    $laravel = FeedSource::factory()->create(['name' => 'Laravel News']);
    $other = FeedSource::factory()->create(['name' => 'Other']);

    RadarItem::factory()->count(2)->create(['feed_source_id' => $laravel->id]);
    RadarItem::factory()->create(['feed_source_id' => $other->id]);

    $this->get(route('radar.index', ['feed' => $laravel->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('items.data', 2)
            ->where('filters.feed', (string) $laravel->id)
            ->has('feeds', 2));
});

test('filters combine, and survive paging', function () {
    $feed = FeedSource::factory()->create();

    RadarItem::factory()->count(30)->create([
        'feed_source_id' => $feed->id,
        'title' => 'Postgres release note',
    ]);
    RadarItem::factory()->count(5)->create(['title' => 'Something else']);

    $response = $this->get(route('radar.index', ['q' => 'postgres', 'feed' => $feed->id]));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->has('items.data', 25)
        ->where('items.total', 30));

    $next = $response->viewData('page')['props']['items']['next_page_url'];

    expect($next)->toContain('q=postgres')
        ->and($next)->toContain('feed='.$feed->id);
});

test('a discarded item stays out of a search of the open queue', function () {
    RadarItem::factory()->discarded()->create(['title' => 'Postgres, dismissed']);
    RadarItem::factory()->create(['title' => 'Postgres, open']);

    $this->get(route('radar.index', ['q' => 'postgres']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('items.data', 1));

    $this->get(route('radar.index', ['q' => 'postgres', 'status' => 'discarded']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('items.data', 1));
});
