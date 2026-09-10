<?php

namespace App\Console\Commands;

use App\Actions\FetchFeedSource;
use App\Models\FeedSource;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('clearsight:fetch-feeds {--source= : Fetch only the feed source with this id}')]
#[Description('Fetch every active feed source and store anything new')]
class FetchFeedsCommand extends Command
{
    public function handle(FetchFeedSource $fetch): int
    {
        $sources = FeedSource::query()
            ->where('is_active', true)
            ->when($this->option('source'), fn ($query, $id) => $query->whereKey($id))
            ->get();

        if ($sources->isEmpty()) {
            $this->info('No active feed sources to fetch.');

            return self::SUCCESS;
        }

        $total = 0;

        foreach ($sources as $source) {
            $stored = $fetch($source);
            $total += $stored;

            if ($source->last_error !== null) {
                $this->error("{$source->name}: {$source->last_error}");

                continue;
            }

            $this->info("{$source->name}: {$stored} new");
        }

        $this->info("{$total} new radar items.");

        return self::SUCCESS;
    }
}
