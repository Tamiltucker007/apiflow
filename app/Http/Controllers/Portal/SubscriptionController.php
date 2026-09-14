<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ChangePlanRequest;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use App\Services\SubscriptionUsageSnapshot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionUsageSnapshot $usage,
        private SubscriptionService $subscriptions,
    ) {}

    public function show(): View|RedirectResponse
    {
        $subscription = $this->activeSubscription();

        if (! $subscription instanceof Subscription) {
            return $subscription;
        }

        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $otherPlans = $customer->merchant->plans()->active()
            ->where('id', '!=', $subscription->plan_id)
            ->orderBy('base_price_cents')
            ->get();

        return view('portal.subscription', [
            'subscription' => $subscription,
            'plan' => $subscription->plan,
            'otherPlans' => $otherPlans,
        ]);
    }

    public function changePlan(ChangePlanRequest $request): RedirectResponse
    {
        $subscription = $this->activeSubscription();

        if (! $subscription instanceof Subscription) {
            return $subscription;
        }

        $newPlan = Plan::findOrFail($request->validated('plan_id'));

        $this->subscriptions->changePlan($subscription, $newPlan);

        return redirect()->route('subscription')->with('status', "Plan changed to {$newPlan->name}, effective today.");
    }

    public function usage(): View|RedirectResponse
    {
        $subscription = $this->activeSubscription();

        if (! $subscription instanceof Subscription) {
            return $subscription;
        }

        $snapshot = $this->usage->forSubscription($subscription);

        return view('portal.usage', [
            'plan' => $snapshot['plan'],
            'usedUnits' => $snapshot['usedUnits'],
            'overageUnits' => $snapshot['overageUnits'],
            'percentage' => $snapshot['percentage'],
            'usageTrend' => $this->usage->dailyTrend($subscription->id),
        ]);
    }

    private function activeSubscription(): Subscription|RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $subscription = $customer->activeSubscription()->with('plan')->first();

        return $subscription ?? redirect()->route('plans.choose');
    }
}
