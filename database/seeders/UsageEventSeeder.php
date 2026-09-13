<?php

namespace Database\Seeders;

use App\Jobs\AggregateUsageJob;
use App\Models\Customer;
use App\Models\DailyUsage;
use App\Models\Merchant;
use App\Models\UsageEvent;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Deliberately varied usage per customer so the dashboard has something to
 * show: Beta Retail is the top customer and exceeds its allowance (overage
 * demo), Nova Traders drops >50% month-over-month (churn-risk demo).
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
    // Beta Retail's rate is deliberately high enough to exceed the Pro
    // plan's 250,000/month allowance within just a few days.
    private const DAILY_UNITS = [
        'ABC Forex Pvt Ltd' => 800,
        'Beta Retail Pvt Ltd' => 30000,
        'Craft Foods Co.' => 1000,
        'Nova Traders' => 50,
        'QuickMart' => 300,
    ];

    // previous_month_total = current_month_to_date * multiplier.
    // >2x -> counts as churn risk (>50% drop); <2x does not.
    private const PREVIOUS_MONTH_MULTIPLIER = [
        'ABC Forex Pvt Ltd' => 1.2,
        'Beta Retail Pvt Ltd' => 0.85, // growing, not shrinking
        'Craft Foods Co.' => 1.15,
        'Nova Traders' => 6.0,          // -> churn risk
        'QuickMart' => 1.1,
    ];

    public function run(): void
    {
        $finpay = Merchant::where('slug', 'finpay')->firstOrFail();
        $customers = Customer::where('merchant_id', $finpay->id)->get()->keyBy('name');

        $periodStart = Carbon::today()->startOfMonth();
        $today = Carbon::today();
        $daysElapsed = $periodStart->diffInDays($today) + 1;
        $rows = [];

        // Current month: raw usage_events, split into 2 events/day so the
        // aggregation job has more than one row per customer/date to group.
        foreach (self::DAILY_UNITS as $customerName => $dailyUnits) {
            $customer = $customers[$customerName];
            $subscriptionId = $customer->activeSubscription->id;

            for ($date = $periodStart->copy(); $date->lte($today); $date->addDay()) {
                $morning = intdiv($dailyUnits, 2);
                $evening = $dailyUnits - $morning;

                foreach ([$morning, $evening] as $units) {
                    $rows[] = [
                        'merchant_id' => $finpay->id,
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
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            UsageEvent::insert($chunk);
        }

        // Roll the raw events into daily_usage via the real aggregation job,
        // exactly as the hourly schedule would.
        AggregateUsageJob::dispatchSync();

        // Previous month: seeded directly as an already-aggregated total
        // (a real system would have aggregated these months ago).
        $previousMonthDay = Carbon::today()->subMonthNoOverflow()->startOfMonth()->addDays(14);

        foreach (self::PREVIOUS_MONTH_MULTIPLIER as $customerName => $multiplier) {
            $customer = $customers[$customerName];
            $currentTotal = self::DAILY_UNITS[$customerName] * $daysElapsed;

            DailyUsage::create([
                'merchant_id' => $finpay->id,
                'customer_id' => $customer->id,
                'subscription_id' => $customer->activeSubscription->id,
                'usage_date' => $previousMonthDay->toDateString(),
                'total_units' => (int) round($currentTotal * $multiplier),
            ]);
        }
    }
}
