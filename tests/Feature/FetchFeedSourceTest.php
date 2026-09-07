<?php

use App\Actions\FetchFeedSource;
use App\Enums\TriageStatus;
use App\Models\FeedSource;
use App\Models\RadarItem;
use Illuminate\Support\Facades\Http;

function rssFeed(): string
{
    return <<<'XML'
<?xml version="1.0"?>
<rss version="2.0">
  <channel>
    <title>Example blog</title>
    <item>
      <title>Postgres 18 is out</title>
      <link>https://example.test/postgres-18</link>
      <pubDate>Mon, 01 Jun 2026 09:00:00 +0000</pubDate>
    </item>
    <item>
      <title>Queues without tears</title>
      <link>https://example.test/queues</link>
      <pubDate>Tue, 02 Jun 2026 09:00:00 +0000</pubDate>
    </item>
  </channel>
</rss>
XML;
}

function atomFeed(): string
{
    return <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title>Example journal</title>
  <entry>
    <title>On watchtowers</title>
    <link rel="alternate" href="https://example.test/watchtowers"/>
    <published>2026-06-03T09:00:00Z</published>
  </entry>
</feed>
XML;
}

test('it stores items from an rss feed', function () {
    Http::fake(['*' => Http::response(rssFeed())]);

    $source = FeedSource::factory()->create();

    expect(app(FetchFeedSource::class)($source))->toBe(2);

    $item = RadarItem::where('url', 'https://example.test/postgres-18')->sole();

    expect($item->title)->toBe('Postgres 18 is out')
        ->and($item->feed_source_id)->toBe($source->id)
        ->and($item->triage_status)->toBe(TriageStatus::Pending)
        ->and($item->published_at->toDateString())->toBe('2026-06-01')
        ->and($item->fetched_at)->not->toBeNull()
        ->and($source->refresh()->last_fetched_at)->not->toBeNull();
});

test('it stores items from an atom feed', function () {
    Http::fake(['*' => Http::response(atomFeed())]);

    $source = FeedSource::factory()->create();

    expect(app(FetchFeedSource::class)($source))->toBe(1);

    expect(RadarItem::sole()->url)->toBe('https://example.test/watchtowers');
});

test('a re-fetch does not resurface an item that was already triaged', function () {
    Http::fake(['*' => Http::response(rssFeed())]);

    $source = FeedSource::factory()->create();
    $fetch = app(FetchFeedSource::class);

    $fetch($source);

    RadarItem::where('url', 'https://example.test/queues')
        ->sole()
        ->update(['triage_status' => TriageStatus::Discarded]);

    expect($fetch($source))->toBe(0)
        ->and(RadarItem::count())->toBe(2)
        ->and(RadarItem::where('url', 'https://example.test/queues')->sole()->triage_status)
        ->toBe(TriageStatus::Discarded);
});

test('it records a failed fetch on the source instead of throwing', function () {
    Http::fake(['*' => Http::response('nope', 500)]);

    $source = FeedSource::factory()->create();

    expect(app(FetchFeedSource::class)($source))->toBe(0)
        ->and($source->refresh()->last_error)->not->toBeNull()
        ->and($source->last_fetched_at)->not->toBeNull()
        ->and(RadarItem::count())->toBe(0);
});

test('it survives a feed that is not valid xml', function () {
    Http::fake(['*' => Http::response('<rss><channel><item>')]);

    $source = FeedSource::factory()->create();

    expect(app(FetchFeedSource::class)($source))->toBe(0)
        ->and($source->refresh()->last_error)->not->toBeNull();
});

test('it skips entries with no title or link', function () {
    Http::fake(['*' => Http::response(<<<'XML'
<?xml version="1.0"?>
<rss version="2.0"><channel>
  <item><title>Keeps this</title><link>https://example.test/keep</link></item>
  <item><title>No link</title></item>
  <item><link>https://example.test/no-title</link></item>
</channel></rss>
XML)]);

    expect(app(FetchFeedSource::class)(FeedSource::factory()->create()))->toBe(1);
});

test('the command fetches only active sources', function () {
    Http::fake(['*' => Http::response(rssFeed())]);

    FeedSource::factory()->create();
    FeedSource::factory()->inactive()->create();

    $this->artisan('specula:fetch-feeds')->assertSuccessful();

    expect(RadarItem::count())->toBe(2);
});
