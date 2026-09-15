<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPlanChange;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    public function __construct(private BillingService $billing) {}

    public function subscribe(Customer $customer, Plan $plan): Subscription
    {
        $alreadySubscribed = Subscription::where('customer_id', $customer->id)
            ->where('status', SubscriptionStatus::Active)
            ->exists();

        if ($alreadySubscribed) {
            throw ValidationException::withMessages([
                'plan_id' => 'This customer already has an active subscription.',
            ]);
        }

        $start = Carbon::today();

        return Subscription::create([
            'merchant_id' => $customer->merchant_id,
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'current_period_start' => $start,
            'current_period_end' => $this->calculatePeriodEnd($start, $plan),
            'started_at' => now(),
        ]);
    }

    /**
     * Records the change and switches the subscription to the new plan.
     * Period dates are untouched — the billing engine splits the existing
     * period into segments via ProrationService::calculateSegments().
     */
    public function changePlan(Subscription $subscription, Plan $newPlan): Subscription
    {
        if ($newPlan->id === $subscription->plan_id) {
            throw ValidationException::withMessages([
                'plan_id' => 'Customer is already on this plan.',
            ]);
        }

        SubscriptionPlanChange::create([
            'subscription_id' => $subscription->id,
            'old_plan_id' => $subscription->plan_id,
            'new_plan_id' => $newPlan->id,
            'changed_at' => now(),
            'effective_date' => Carbon::today(),
        ]);

        $subscription->update(['plan_id' => $newPlan->id]);

        return $subscription->fresh();
    }

    /**
     * Cancels a subscription effective immediately. Bills a final invoice
     * for the days actually used this cycle (period_start through today) —
     * there's nothing to "refund" since the un-used remainder was never
     * billed in the first place; this is the mirror of that. Skipped if the
     * current cycle was already invoiced (e.g. admin generated it on-demand
     * earlier), so a cancel never double-bills an overlapping range.
     */
    public function cancel(Subscription $subscription): Subscription
    {
        if ($subscription->status !== SubscriptionStatus::Active) {
            throw ValidationException::withMessages([
                'subscription' => 'This subscription is not active.',
            ]);
        }

        // whereDate() (not where()) since a date-cast column round-trips
        // with a time component on some drivers (SQLite) — an exact string
        // match against toDateString() would silently never hit.
        $alreadyInvoicedThisCycle = Invoice::where('subscription_id', $subscription->id)
            ->whereDate('period_start', $subscription->current_period_start->toDateString())
            ->exists();

        if (! $alreadyInvoicedThisCycle) {
            $cancelledThrough = Carbon::today()->min($subscription->current_period_end);
            $this->billing->generateInvoice($subscription, $subscription->current_period_start, $cancelledThrough);
        }

        // TODO: Need to cancel subscription in Stripe

        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        return $subscription->fresh();
    }

    // Rolls the subscription into its next billing period, using its
    // (possibly just-changed) current plan for the new period's length.
    public function advanceToNextPeriod(Subscription $subscription): Subscription
    {
        $start = $subscription->current_period_end->copy()->addDay();

        $subscription->update([
            'current_period_start' => $start,
            'current_period_end' => $this->calculatePeriodEnd($start, $subscription->plan),
        ]);

        return $subscription->fresh();
    }

    private function calculatePeriodEnd(Carbon $start, Plan $plan): Carbon
    {
        return $plan->billing_cycle->periodEnd($start);
    }
}
