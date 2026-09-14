<?php

namespace Database\Seeders;

use App\Jobs\AggregateUsageJob;
use App\Models\Customer;
use App\Models\DailyUsage;
use App\Models\Merchant;
use App\Models\UsageEvent;
use Carbon\Carbon;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Deliberately varied usage per customer so the dashboard has something to
 * show: each merchant gets one customer that exceeds its allowance (overage
 * demo) and one whose usage dropped >50% month-over-month (churn-risk demo)
 * — see DemoData for which customer plays which role, per merchant.
 *
 * Previous-month totals are a MULTIPLE of the current month-to-date total
 * (not a fixed number) — the dashboard compares a full previous month
 * against however much of the current month has elapsed, so a fixed
 * previous-month number would make the "drop" ratio depend on which day
 * of the month this seeder happens to run on. Scaling by the current
 * total keeps the ratios (and which customers count as churn risk) stable
 * regardless of the actual seeding date.
 */
class UsageEventSeeder extends Seeder
{
    public function run(): void
    {
        $periodStart = Carbon::today()->startOfMonth();
        $today = Carbon::today();
        $daysElapsed = $periodStart->diffInDays($today) + 1;
        $previousMonthDay = Carbon::today()->subMonthNoOverflow()->startOfMonth()->addDays(14);

        $eventRows = [];
        $dailyUsageRows = [];

        foreach (DemoData::merchants() as $slug => $data) {
            $merchant = Merchant::where('slug', $slug)->firstOrFail();
            $customers = Customer::where('merchant_id', $merchant->id)->get()->keyBy('name');

            foreach ($data['customers'] as $customerData) {
                $customer = $customers[$customerData['name']];
                $subscriptionId = $customer->activeSubscription->id;
                $dailyUnits = $customerData['daily_units'];

                // Current month: raw usage_events, split into 2 events/day so
                // the aggregation job has more than one row per
                // customer/date to group.
                for ($date = $periodStart->copy(); $date->lte($today); $date->addDay()) {
                    $morning = intdiv($dailyUnits, 2);
                    $evening = $dailyUnits - $morning;

                    foreach ([$morning, $evening] as $units) {
                        $eventRows[] = [
                            'merchant_id' => $merchant->id,
                            'customer_id' => $customer->id,
                            'subscription_id' => $subscriptionId,
                            'event_key' => (string) Str::uuid(),
                            'units' => $units,
                            'recorded_date' => $date->toDateString(),
                            'metadata' => null,
                            'is_aggregated' => false,
                            'created_at' => now(),
                        ];
                    }
                }

                // Previous month: seeded directly as an already-aggregated
                // total (a real system would have aggregated these months ago).
                $currentTotal = $dailyUnits * $daysElapsed;
                $dailyUsageRows[] = [
                    'merchant_id' => $merchant->id,
                    'customer_id' => $customer->id,
                    'subscription_id' => $subscriptionId,
                    'usage_date' => $previousMonthDay->toDateString(),
                    'total_units' => (int) round($currentTotal * $customerData['previous_month_multiplier']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($eventRows, 500) as $chunk) {
            UsageEvent::insert($chunk);
        }

        // Roll the raw events into daily_usage via the real aggregation job,
        // exactly as the hourly schedule would — across every merchant in
        // one pass, same as production.
        AggregateUsageJob::dispatchSync();

        DailyUsage::insert($dailyUsageRows);
    }
}
