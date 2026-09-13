<?php

namespace App\Console\Commands;

use App\Jobs\AggregateUsageJob;
use Illuminate\Console\Command;

class AggregateUsageCommand extends Command
{
    protected $signature = 'usage:aggregate {--sync : Run immediately instead of dispatching to the queue}';

    protected $description = 'Roll un-aggregated usage_events into daily_usage';

    public function handle(): int
    {
        if ($this->option('sync')) {
            AggregateUsageJob::dispatchSync();
        } else {
            AggregateUsageJob::dispatch();
        }

        $this->info('Usage aggregation dispatched.');

        return self::SUCCESS;
    }
}
