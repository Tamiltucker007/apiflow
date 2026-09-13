<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
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
        $end = $start->copy()->addMonthsNoOverflow($plan->billing_cycle->months())->subDay();

        return Subscription::create([
            'merchant_id' => $customer->merchant_id,
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'current_period_start' => $start,
            'current_period_end' => $end,
            'started_at' => now(),
        ]);
    }
}
