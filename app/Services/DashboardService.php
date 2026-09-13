<?php

namespace App\Services;

use App\Models\Merchant;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// All methods use joined query-builder aggregates rather than looping per
// subscription/customer in PHP — subscriptions can each have a different
// current_period, so the join matches daily_usage.usage_date against each
// row's own period rather than one merchant-wide date range.
class DashboardService
{
    public function getCurrentCycleUsage(Merchant $merchant): array
    {
        $totalUnits = (int) DB::table('daily_usage')
            ->join('subscriptions', 'daily_usage.subscription_id', '=', 'subscriptions.id')
            ->where('subscriptions.merchant_id', $merchant->id)
            ->where('subscriptions.status', 'active')
            ->whereColumn('daily_usage.usage_date', '>=', 'subscriptions.current_period_start')
            ->whereColumn('daily_usage.usage_date', '<=', 'subscriptions.current_period_end')
            ->sum('daily_usage.total_units');

        $includedUnits = (int) DB::table('subscriptions')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('subscriptions.merchant_id', $merchant->id)
            ->where('subscriptions.status', 'active')
            ->sum('plans.included_units');

        return ['total_units' => $totalUnits, 'included_units' => $includedUnits];
    }

    public function getTopCustomersByUsage(Merchant $merchant, int $limit = 5): Collection
    {
        return DB::table('daily_usage')
            ->join('subscriptions', 'daily_usage.subscription_id', '=', 'subscriptions.id')
            ->join('customers', 'subscriptions.customer_id', '=', 'customers.id')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('subscriptions.merchant_id', $merchant->id)
            ->where('subscriptions.status', 'active')
            ->whereColumn('daily_usage.usage_date', '>=', 'subscriptions.current_period_start')
            ->whereColumn('daily_usage.usage_date', '<=', 'subscriptions.current_period_end')
            ->groupBy('customers.id', 'customers.name', 'plans.included_units')
            ->select(
                'customers.name as customer_name',
                'plans.included_units',
                DB::raw('SUM(daily_usage.total_units) as total_units'),
            )
            ->orderByDesc('total_units')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => (object) [
                'customer_name' => $row->customer_name,
                'total_units' => (int) $row->total_units,
                'percentage' => $row->included_units > 0 ? (int) round($row->total_units / $row->included_units * 100) : 0,
            ]);
    }

    public function getProjectedOverageRevenue(Merchant $merchant): int
    {
        $subscriptions = DB::table('subscriptions')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('subscriptions.merchant_id', $merchant->id)
            ->where('subscriptions.status', 'active')
            ->select('subscriptions.id', 'subscriptions.current_period_start', 'subscriptions.current_period_end', 'plans.included_units', 'plans.overage_rate_cents')
            ->get();

        $today = Carbon::today();
        $revenue = 0;

        foreach ($subscriptions as $sub) {
            $start = Carbon::parse($sub->current_period_start);
            $end = Carbon::parse($sub->current_period_end);
            $totalDays = $start->diffInDays($end) + 1;
            $daysElapsed = max(0, min($totalDays, $start->diffInDays($today) + 1));
            $progressRatio = $totalDays > 0 ? $daysElapsed / $totalDays : 0;

            if ($progressRatio <= 0) {
                continue;
            }

            $currentUsage = (int) DB::table('daily_usage')
                ->where('subscription_id', $sub->id)
                ->whereBetween('usage_date', [$sub->current_period_start, $sub->current_period_end])
                ->sum('total_units');

            $projectedUsage = $currentUsage / $progressRatio;
            $projectedOverage = max(0, $projectedUsage - $sub->included_units);
            $revenue += $projectedOverage * $sub->overage_rate_cents;
        }

        return (int) round($revenue);
    }

    public function getChurnRiskCustomers(Merchant $merchant): Collection
    {
        $currentMonth = Carbon::today();
        $previousMonth = $currentMonth->copy()->subMonthNoOverflow();

        $current = $this->monthlyUsageByCustomer($merchant, $currentMonth);
        $previous = $this->monthlyUsageByCustomer($merchant, $previousMonth);

        return $previous
            ->filter(fn ($prevUnits) => $prevUnits > 0)
            ->map(function ($prevUnits, $customerId) use ($current) {
                $currUnits = $current->get($customerId, 0);

                return [
                    'current' => $currUnits,
                    'previous' => $prevUnits,
                    'drop' => $prevUnits > 0 ? (1 - $currUnits / $prevUnits) * 100 : 0,
                ];
            })
            ->filter(fn ($row) => $row['drop'] > 50)
            ->sortByDesc('drop')
            ->map(function ($row, $customerId) {
                return (object) [
                    'customer_name' => DB::table('customers')->where('id', $customerId)->value('name'),
                    'current_usage' => $row['current'],
                    'previous_usage' => $row['previous'],
                    'drop_percentage' => (int) round($row['drop']),
                ];
            })
            ->values();
    }

    public function getDailyUsageTrend(Merchant $merchant, int $days = 30): Collection
    {
        $start = Carbon::today()->subDays($days - 1);

        $rows = DB::table('daily_usage')
            ->where('merchant_id', $merchant->id)
            ->where('usage_date', '>=', $start->toDateString())
            ->groupBy('usage_date')
            ->select('usage_date', DB::raw('SUM(total_units) as units'))
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->usage_date)->toDateString());

        return collect(range(0, $days - 1))->map(function ($offset) use ($start, $rows) {
            $date = $start->copy()->addDays($offset)->toDateString();

            return ['date' => $date, 'units' => (int) ($rows->get($date)->units ?? 0)];
        });
    }

    private function monthlyUsageByCustomer(Merchant $merchant, Carbon $referenceMonth): Collection
    {
        return DB::table('daily_usage')
            ->where('merchant_id', $merchant->id)
            ->whereBetween('usage_date', [
                $referenceMonth->copy()->startOfMonth()->toDateString(),
                $referenceMonth->copy()->endOfMonth()->toDateString(),
            ])
            ->groupBy('customer_id')
            ->select('customer_id', DB::raw('SUM(total_units) as units'))
            ->pluck('units', 'customer_id');
    }
}
