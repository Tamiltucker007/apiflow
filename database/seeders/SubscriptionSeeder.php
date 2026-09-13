<?php

namespace Database\Seeders;

use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPlanChange;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $finpay = Merchant::where('slug', 'finpay')->firstOrFail();
        $plans = Plan::where('merchant_id', $finpay->id)->get()->keyBy('name');
        $customers = Customer::where('merchant_id', $finpay->id)->get()->keyBy('name');

        $periodStart = Carbon::today()->startOfMonth();
        $periodEnd = Carbon::today()->endOfMonth();

        $assignments = [
            'ABC Forex Pvt Ltd' => 'Growth',
            'Beta Retail Pvt Ltd' => 'Pro',
            'Craft Foods Co.' => 'Growth',
            'Nova Traders' => 'Starter',
            'QuickMart' => 'Starter',
        ];

        $subscriptions = [];

        foreach ($assignments as $customerName => $planName) {
            $subscriptions[$customerName] = Subscription::create([
                'merchant_id' => $finpay->id,
                'customer_id' => $customers[$customerName]->id,
                'plan_id' => $plans[$planName]->id,
                'status' => SubscriptionStatus::Active,
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
                'started_at' => $periodStart,
            ]);
        }

        // ABC Forex: started the month on Growth, upgraded to Pro mid-month —
        // demonstrates the mid-cycle plan change / proration split.
        $changeDate = $periodStart->copy()->day(15);
        if ($changeDate->gt(Carbon::today())) {
            $changeDate = $periodStart->copy()->addDay();
        }

        $abcForex = $subscriptions['ABC Forex Pvt Ltd'];
        SubscriptionPlanChange::create([
            'subscription_id' => $abcForex->id,
            'old_plan_id' => $plans['Growth']->id,
            'new_plan_id' => $plans['Pro']->id,
            'changed_at' => $changeDate,
            'effective_date' => $changeDate,
        ]);
        $abcForex->update(['plan_id' => $plans['Pro']->id]);
    }
}
