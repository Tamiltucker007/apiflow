<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Services\PlanService;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = new PlanService;

        foreach (DemoData::merchants() as $slug => $data) {
            $merchant = Merchant::where('slug', $slug)->firstOrFail();

            foreach ($data['plans'] as $planData) {
                $plans->create($merchant, $planData);
            }
        }
    }
}
