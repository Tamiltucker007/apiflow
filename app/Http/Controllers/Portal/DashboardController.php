<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Jobs\AggregateUsageJob;
use App\Models\Customer;
use App\Services\SubscriptionUsageSnapshot;
use App\Services\UsageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private SubscriptionUsageSnapshot $usage) {}

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

        $snapshot = $this->usage->forSubscription($subscription);

        $invoices = $customer->invoices()->latest('issued_at')->take(5)->get();

        return view('portal.dashboard', [
            'customer' => $customer,
            'subscription' => $subscription,
            'plan' => $snapshot['plan'],
            'usedUnits' => $snapshot['usedUnits'],
            'overageUnits' => $snapshot['overageUnits'],
            'percentage' => $snapshot['percentage'],
            'invoices' => $invoices,
            'usageTrend' => $this->usage->dailyTrend($subscription->id),
        ]);
    }

    /**
     * Demo/dev helper: same trick as CustomerController::simulateUsage()
     * (admin side), scoped to the logged-in customer instead of a route
     * param — lets a self-service walkthrough show the usage -> billing
     * flow without an admin tab or a real API client. Disabled in production.
     */
    public function simulateUsage(UsageService $usage): RedirectResponse
    {
        abort_if(app()->isProduction(), 404);

        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        try {
            $result = $usage->simulateUsage($customer);
        } catch (ValidationException) {
            return redirect()->route('dashboard')->with('error', 'No active subscription to simulate usage against.');
        }

        AggregateUsageJob::dispatchSync();

        return redirect()->route('dashboard')
            ->with('status', "Simulated {$result['total_units']} usage units across {$result['days']} day(s). Dashboard updated.");
    }
}
