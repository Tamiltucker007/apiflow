<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DailyUsage;
use App\Services\PlanPricingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private PlanPricingService $pricing) {}

    // Deliberately queries directly rather than reusing DashboardService:
    // that service aggregates across a merchant's whole customer base for
    // the admin dashboard, while this is always exactly one customer's own
    // data — a merchant-wide join would be the wrong tool and a needless
    // way to leak scope into a self-service page.
    public function __invoke(): View|RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $subscription = $customer->activeSubscription()->with('plan')->first();

        // A freshly-registered (or ex-subscriber) customer has nothing to
        // show here yet — send them to pick a plan instead of a dashboard
        // that's all zeroes.
        if (! $subscription) {
            return redirect()->route('plans.choose');
        }

        $plan = $this->pricing->getCachedPlan($subscription->plan_id);

        $usedUnits = (int) DailyUsage::where('subscription_id', $subscription->id)
            ->whereBetween('usage_date', [$subscription->current_period_start, $subscription->current_period_end])
            ->sum('total_units');

        $overageUnits = max(0, $usedUnits - $plan->included_units);

        $invoices = $customer->invoices()->latest('issued_at')->take(10)->get();

        $usageTrend = $this->dailyUsageTrend($subscription->id);

        return view('portal.dashboard', [
            'customer' => $customer,
            'subscription' => $subscription,
            'plan' => $plan,
            'usedUnits' => $usedUnits,
            'overageUnits' => $overageUnits,
            'invoices' => $invoices,
            'usageTrend' => $usageTrend,
        ]);
    }

    // Same shape as DashboardService::getDailyUsageTrend() (30 days, zero-
    // filled gaps so the bar chart never has a hole for a day with no
    // recorded usage) but scoped to one subscription instead of a merchant's
    // whole customer base.
    private function dailyUsageTrend(int $subscriptionId, int $days = 30)
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
