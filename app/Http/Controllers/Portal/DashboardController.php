<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DailyUsage;
use App\Services\PlanPricingService;
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
    public function __invoke(): View
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $subscription = $customer->activeSubscription()->with('plan')->first();

        $usedUnits = 0;
        $plan = null;

        if ($subscription) {
            $plan = $this->pricing->getCachedPlan($subscription->plan_id);

            $usedUnits = (int) DailyUsage::where('subscription_id', $subscription->id)
                ->whereBetween('usage_date', [$subscription->current_period_start, $subscription->current_period_end])
                ->sum('total_units');
        }

        $overageUnits = $plan ? max(0, $usedUnits - $plan->included_units) : 0;

        $invoices = $customer->invoices()->latest('issued_at')->take(10)->get();

        return view('portal.dashboard', [
            'customer' => $customer,
            'subscription' => $subscription,
            'plan' => $plan,
            'usedUnits' => $usedUnits,
            'overageUnits' => $overageUnits,
            'invoices' => $invoices,
        ]);
    }
}
