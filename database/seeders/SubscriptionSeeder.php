<?php

namespace Database\Seeders;

use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPlanChange;
use Carbon\Carbon;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $periodStart = Carbon::today()->startOfMonth();
        $periodEnd = Carbon::today()->endOfMonth();

        foreach (DemoData::merchants() as $slug => $data) {
            $merchant = Merchant::where('slug', $slug)->firstOrFail();
            $plans = Plan::where('merchant_id', $merchant->id)->get()->keyBy('name');
            $customers = Customer::where('merchant_id', $merchant->id)->get()->keyBy('name');

            foreach ($data['customers'] as $customerData) {
                $customer = $customers[$customerData['name']];
                $plan = $plans[$customerData['plan']];

                $subscription = Subscription::create([
                    'merchant_id' => $merchant->id,
                    'customer_id' => $customer->id,
                    'plan_id' => $plan->id,
                    'status' => SubscriptionStatus::Active,
                    'current_period_start' => $periodStart,
                    'current_period_end' => $periodEnd,
                    'started_at' => $periodStart,
                ]);

                // e.g. FinPay's ABC Forex: started the month on one plan,
                // upgraded mid-month — demonstrates the mid-cycle plan
                // change / proration split.
                if (! empty($customerData['plan_change_to'])) {
                    $newPlan = $plans[$customerData['plan_change_to']];
                    $changeDate = $periodStart->copy()->day(15);
                    if ($changeDate->gt(Carbon::today())) {
                        $changeDate = $periodStart->copy()->addDay();
                    }

                    SubscriptionPlanChange::create([
                        'subscription_id' => $subscription->id,
                        'old_plan_id' => $plan->id,
                        'new_plan_id' => $newPlan->id,
                        'changed_at' => $changeDate,
                        'effective_date' => $changeDate,
                    ]);
                    $subscription->update(['plan_id' => $newPlan->id]);
                }
            }
        }
    }
}
