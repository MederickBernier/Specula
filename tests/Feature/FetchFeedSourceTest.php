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
      <description>&lt;p&gt;The &lt;b&gt;release&lt;/b&gt; lands today.&lt;/p&gt;</description>
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
    <summary>A short note on lookouts.</summary>
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
        ->and($item->summary)->toBe('The release lands today.')
        ->and($item->fetched_at)->not->toBeNull()
        ->and($source->refresh()->last_fetched_at)->not->toBeNull();
});

test('it stores items from an atom feed', function () {
    Http::fake(['*' => Http::response(atomFeed())]);

    $source = FeedSource::factory()->create();

    expect(app(FetchFeedSource::class)($source))->toBe(1);

    expect(RadarItem::sole()->url)->toBe('https://example.test/watchtowers')
        ->and(RadarItem::sole()->summary)->toBe('A short note on lookouts.');
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

    $this->artisan('clearsight:fetch-feeds')->assertSuccessful();

    expect(RadarItem::count())->toBe(2);
});

test('it stores feed text as plain text, never as the markup that arrived', function () {
    Http::fake(['*' => Http::response(<<<'XML'
<?xml version="1.0"?>
<rss version="2.0"><channel>
  <item>
    <title>Tidy &amp;amp; short</title>
    <link>https://example.test/xss</link>
    <description>&lt;script&gt;alert('x')&lt;/script&gt;Real   summary
    text</description>
  </item>
</channel></rss>
XML)]);

    app(FetchFeedSource::class)(FeedSource::factory()->create());

    $item = RadarItem::sole();

    expect($item->summary)->toBe("alert('x')Real summary text")
        ->and($item->summary)->not->toContain('<script>')
        ->and($item->title)->toBe('Tidy & short');
});

test('it stores no summary when the feed gives none', function () {
    Http::fake(['*' => Http::response(<<<'XML'
<?xml version="1.0"?>
<rss version="2.0"><channel>
  <item><title>Bare</title><link>https://example.test/bare</link></item>
</channel></rss>
XML)]);

    app(FetchFeedSource::class)(FeedSource::factory()->create());

    expect(RadarItem::sole()->summary)->toBeNull();
});

test('the seed command adds the starter feeds once', function () {
    $this->artisan('clearsight:seed-feeds')->assertSuccessful();

    $first = FeedSource::count();

    expect($first)->toBeGreaterThan(0);

    $this->artisan('clearsight:seed-feeds')->assertSuccessful();

    expect(FeedSource::count())->toBe($first)
        ->and(FeedSource::pluck('url')->unique())->toHaveCount($first);
});

test('the seed command leaves a source you already changed alone', function () {
    $this->artisan('clearsight:seed-feeds');

    $source = FeedSource::query()->firstOrFail();
    $source->update(['name' => 'Renamed', 'is_active' => false]);

    $this->artisan('clearsight:seed-feeds');

    expect($source->refresh()->name)->toBe('Renamed')
        ->and($source->is_active)->toBeFalse();
});
