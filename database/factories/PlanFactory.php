<?php

namespace Database\Factories;

use App\Enums\BillingCycle;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'merchant_id' => Merchant::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'base_price_cents' => fake()->numberBetween(9900, 999900),
            'currency' => 'INR',
            'billing_cycle' => BillingCycle::Monthly,
            'included_units' => fake()->numberBetween(1000, 100000),
            'overage_rate_cents' => fake()->numberBetween(5, 50),
            'is_active' => true,
        ];
    }
}
