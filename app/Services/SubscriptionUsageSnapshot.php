<?php

namespace App\Services;

use App\Models\DailyUsage;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Collection;

// Shared by the customer portal's Dashboard and Subscription & Usage pages
// so both compute the same numbers the same way instead of duplicating the
// query logic.
class SubscriptionUsageSnapshot
{
    public function __construct(private PlanPricingService $pricing) {}

    /**
     * @return array{plan: Plan, usedUnits: int, overageUnits: int, percentage: int}
     */
    public function forSubscription(Subscription $subscription): array
    {
        $plan = $this->pricing->getCachedPlan($subscription->plan_id);

        $usedUnits = (int) DailyUsage::where('subscription_id', $subscription->id)
            ->whereBetween('usage_date', [$subscription->current_period_start, $subscription->current_period_end])
            ->sum('total_units');

        $overageUnits = max(0, $usedUnits - $plan->included_units);
        $percentage = $plan->included_units > 0 ? min(100, (int) round($usedUnits / $plan->included_units * 100)) : 0;

        return compact('plan', 'usedUnits', 'overageUnits', 'percentage');
    }

    // 30 days, zero-filled so the bar chart never has a hole for a day with
    // no recorded usage.
    public function dailyTrend(int $subscriptionId, int $days = 30): Collection
    {
        $start = Carbon::today()->subDays($days - 1);

        $rows = DailyUsage::where('subscription_id', $subscriptionId)
            ->where('usage_date', '>=', $start->toDateString())
            ->pluck('total_units', 'usage_date');

        return collect(range(0, $days - 1))->map(function ($offset) use ($start, $rows) {
            $date = $start->copy()->addDays($offset)->toDateString();

            return ['date' => $date, 'units' => (int) ($rows[$date] ?? 0)];
        });
    }
}
