<?php

namespace App\Actions;

use App\Models\FeedSource;
use App\Models\RadarItem;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;
use Throwable;

/**
 * Fetches one feed and stores anything not seen before.
 *
 * RSS and Atom are both plain XML, so SimpleXML covers this without a feed
 * library. Existing items are never touched: url is the dedup key, and a
 * re-fetch must not resurface something already triaged.
 */
class FetchFeedSource
{
    /**
     * @return int the number of items stored for the first time
     */
    public function __invoke(FeedSource $source): int
    {
        try {
            $body = Http::timeout(15)
                ->withUserAgent('Specula feed reader')
                ->get($source->url)
                ->throw()
                ->body();

            $stored = $this->store($source, $this->parse($body));

            $source->forceFill(['last_fetched_at' => now(), 'last_error' => null])->save();

            return $stored;
        } catch (Throwable $exception) {
            $source->forceFill([
                'last_fetched_at' => now(),
                'last_error' => str($exception->getMessage())->limit(200)->value(),
            ])->save();

            return 0;
        }
    }

    /**
     * @param  list<array{title: string, url: string, published_at: string|null}>  $entries
     */
    private function store(FeedSource $source, array $entries): int
    {
        $seen = RadarItem::query()
            ->whereIn('url', array_column($entries, 'url'))
            ->pluck('url')
            ->all();

        $stored = 0;

        foreach ($entries as $entry) {
            if (in_array($entry['url'], $seen, true)) {
                continue;
            }

            $source->radarItems()->create($entry);
            $seen[] = $entry['url'];
            $stored++;
        }

        return $stored;
    }

    /**
     * @return list<array{title: string, url: string, published_at: string|null}>
     */
    public function parse(string $body): array
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $xml = new SimpleXMLElement($body);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $entries = [];

        foreach ($xml->xpath('//item') ?: [] as $item) {
            $entries[] = [
                'title' => trim((string) $item->title),
                'url' => trim((string) $item->link),
                'published_at' => $this->date((string) $item->pubDate),
            ];
        }

        foreach ($xml->xpath('//*[local-name()="entry"]') ?: [] as $entry) {
            $entries[] = [
                'title' => trim((string) $entry->title),
                'url' => $this->atomLink($entry),
                'published_at' => $this->date(
                    (string) ($entry->published ?? '') ?: (string) ($entry->updated ?? ''),
                ),
            ];
        }

        return array_values(array_filter(
            $entries,
            fn (array $entry): bool => $entry['title'] !== '' && $entry['url'] !== '',
        ));
    }

    /**
     * Atom puts the item URL on a link element's href, preferring rel="alternate".
     */
    private function atomLink(SimpleXMLElement $entry): string
    {
        $fallback = '';

        foreach ($entry->link as $link) {
            $href = trim((string) $link['href']);

            if ($href === '') {
                continue;
            }

            if ((string) $link['rel'] === 'alternate' || (string) $link['rel'] === '') {
                return $href;
            }

            $fallback = $fallback ?: $href;
        }

        return $fallback;
    }

    private function date(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }
}
