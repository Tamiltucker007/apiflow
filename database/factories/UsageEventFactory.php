<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UsageEvent>
 */
class UsageEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'customer_id' => Customer::factory(),
            'subscription_id' => Subscription::factory(),
            'event_key' => (string) Str::uuid(),
            'units' => 1,
            'recorded_date' => now()->toDateString(),
            'metadata' => null,
            'is_aggregated' => false,
        ];
    }
}
