<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->startOfMonth();

        return [
            'merchant_id' => Merchant::factory(),
            'customer_id' => Customer::factory(),
            'plan_id' => Plan::factory(),
            'status' => SubscriptionStatus::Active,
            'current_period_start' => $start,
            'current_period_end' => $start->copy()->endOfMonth(),
            'started_at' => $start,
        ];
    }
}
