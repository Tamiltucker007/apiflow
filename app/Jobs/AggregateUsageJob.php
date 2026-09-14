<?php

namespace App\Jobs;

use App\Models\DailyUsage;
use App\Models\UsageEvent;
use App\Scopes\MerchantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

// Rolls raw usage_events into daily_usage. chunkById avoids the offset-scan
// cost of chunk()/get() at 50L+ rows; the is_aggregated flag means a rerun
// only ever touches events not yet processed, so this job is safe to retry.
class AggregateUsageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    // Must stay below the WithoutOverlapping expireAfter() so a stuck worker
    // is always killed before its lock would otherwise expire on its own.
    public $timeout = 300;

    public function __construct()
    {
        $this->onQueue('aggregation');
    }

    // The job is both hourly-scheduled and manually dispatchable via artisan.
    // Without this, two concurrent runs could both read is_aggregated=false
    // for the same events and double-count them. The lock key is global
    // (not per-merchant) because a single run processes every merchant.
    public function middleware(): array
    {
        return [(new WithoutOverlapping('aggregate-usage'))->releaseAfter(600)->expireAfter(900)];
    }

    public function handle(): void
    {
        // MerchantScope only filters when a request has set tenant context,
        // which a queued job never does — but forcing it off here makes that
        // guarantee explicit rather than relying on the queue never running
        // inside a scoped request.
        UsageEvent::withoutGlobalScope(MerchantScope::class)
            ->unaggregated()
            ->select(['id', 'merchant_id', 'customer_id', 'subscription_id', 'recorded_date', 'units'])
            ->chunkById(5000, function ($events) {
                // Wraps each chunk's totals update and its is_aggregated flag
                // in one transaction: if the process dies partway through, both
                // roll back together, so a rerun never double-counts and never
                // leaves a flagged event whose total wasn't actually recorded.
                DB::transaction(function () use ($events) {
                    $grouped = $events->groupBy(
                        fn (UsageEvent $event) => "{$event->merchant_id}_{$event->customer_id}_{$event->subscription_id}_{$event->recorded_date->toDateString()}"
                    );

                    foreach ($grouped as $group) {
                        $first = $group->first();
                        $units = (int) $group->sum('units');

                        // Atomic UPDATE at the DB level — never read total_units
                        // into PHP then write it back. Falls back to an insert
                        // only when no row exists yet for this customer/date.
                        $updated = DailyUsage::withoutGlobalScope(MerchantScope::class)
                            ->where([
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

                    UsageEvent::withoutGlobalScope(MerchantScope::class)
                        ->whereIn('id', $events->pluck('id'))
                        ->update(['is_aggregated' => true]);
                });
            });
    }
}
