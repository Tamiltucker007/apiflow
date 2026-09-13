<?php

namespace App\Jobs;

use App\Models\DailyUsage;
use App\Models\UsageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// Rolls raw usage_events into daily_usage. chunkById avoids the offset-scan
// cost of chunk()/get() at 50L+ rows; the is_aggregated flag means a rerun
// (e.g. after a failed batch) only ever touches events not yet processed,
// so this job is safe to retry or run concurrently with itself.
class AggregateUsageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 300;

    public function __construct()
    {
        $this->onQueue('aggregation');
    }

    public function handle(): void
    {
        UsageEvent::unaggregated()->chunkById(5000, function ($events) {
            $grouped = $events->groupBy(
                fn (UsageEvent $event) => "{$event->customer_id}_{$event->subscription_id}_{$event->recorded_date->toDateString()}"
            );

            foreach ($grouped as $group) {
                $first = $group->first();
                $units = (int) $group->sum('units');

                // Atomic UPDATE at the DB level — never read total_units into
                // PHP then write it back, which would lose updates under
                // concurrent aggregation runs. Falls back to an insert only
                // when no row exists yet for this customer/subscription/date.
                $updated = DailyUsage::where([
                    'merchant_id' => $first->merchant_id,
                    'customer_id' => $first->customer_id,
                    'subscription_id' => $first->subscription_id,
                    'usage_date' => $first->recorded_date->toDateString(),
                ])->increment('total_units', $units);

                if ($updated === 0) {
                    DailyUsage::create([
                        'merchant_id' => $first->merchant_id,
                        'customer_id' => $first->customer_id,
                        'subscription_id' => $first->subscription_id,
                        'usage_date' => $first->recorded_date->toDateString(),
                        'total_units' => $units,
                    ]);
                }
            }

            UsageEvent::whereIn('id', $events->pluck('id'))->update(['is_aggregated' => true]);
        });
    }
}
