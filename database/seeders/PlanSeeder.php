<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Services\PlanService;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $finpay = Merchant::where('slug', 'finpay')->firstOrFail();
        $plans = new PlanService;

        $plans->create($finpay, [
            'name' => 'Starter',
            'billing_cycle' => 'monthly',
            'base_price_cents' => 99900,
            'included_units' => 10000,
            'overage_rate_cents' => 15,
        ]);

        $plans->create($finpay, [
            'name' => 'Growth',
            'billing_cycle' => 'monthly',
            'base_price_cents' => 499900,
            'included_units' => 50000,
            'overage_rate_cents' => 10,
        ]);

        $plans->create($finpay, [
            'name' => 'Pro',
            'billing_cycle' => 'monthly',
            'base_price_cents' => 1499900,
            'included_units' => 250000,
            'overage_rate_cents' => 5,
        ]);
    }
}
