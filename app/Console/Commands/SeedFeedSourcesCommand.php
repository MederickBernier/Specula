<?php

namespace App\Console\Commands;

use App\Enums\FeedType;
use App\Models\FeedSource;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Gives a fresh instance something to pull, so the radar is not an empty page
 * on day one. Safe to run again: sources are matched on their URL, and one you
 * have edited or paused is left alone.
 */
#[Signature('clearsight:seed-feeds {--fetch : Fetch each source once after adding it}')]
#[Description('Add a starter set of tech and security feeds')]
class SeedFeedSourcesCommand extends Command
{
    /**
     * @var list<array{name: string, url: string, type: FeedType}>
     */
    private const FEEDS = [
        ['name' => 'Laravel News', 'url' => 'https://feed.laravel-news.com/', 'type' => FeedType::Rss],
        ['name' => 'PHP.net', 'url' => 'https://www.php.net/feed.atom', 'type' => FeedType::Atom],
        ['name' => 'React blog', 'url' => 'https://react.dev/rss.xml', 'type' => FeedType::Rss],
        ['name' => 'GitHub Engineering', 'url' => 'https://github.blog/engineering.atom', 'type' => FeedType::Atom],
        ['name' => 'PostgreSQL news', 'url' => 'https://www.postgresql.org/news.rss', 'type' => FeedType::Rss],
        ['name' => 'PhpStorm blog', 'url' => 'https://blog.jetbrains.com/phpstorm/feed/', 'type' => FeedType::Rss],
        ['name' => 'Hacker News front page', 'url' => 'https://hnrss.org/frontpage', 'type' => FeedType::Rss],
        ['name' => 'AWS security bulletins', 'url' => 'https://aws.amazon.com/security/security-bulletins/rss/feed/', 'type' => FeedType::Rss],
        ['name' => 'CISA advisories', 'url' => 'https://www.cisa.gov/cybersecurity-advisories/all.xml', 'type' => FeedType::Rss],
        ['name' => 'BleepingComputer', 'url' => 'https://www.bleepingcomputer.com/feed/', 'type' => FeedType::Rss],
    ];

    public function handle(): int
    {
        $added = 0;

        foreach (self::FEEDS as $feed) {
            $source = FeedSource::firstOrCreate(
                ['url' => $feed['url']],
                ['name' => $feed['name'], 'feed_type' => $feed['type'], 'is_active' => true],
            );

            if ($source->wasRecentlyCreated) {
                $added++;
                $this->line("added {$feed['name']}");
            }
        }

        $this->info("{$added} feed source(s) added.");

        if ($this->option('fetch') && $added > 0) {
            $this->call('clearsight:fetch-feeds');
        }

        return self::SUCCESS;
    }
}
