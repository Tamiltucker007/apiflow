<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\Plan;
use Illuminate\Support\Str;

class PlanService
{
    public function create(Merchant $merchant, array $data): Plan
    {
        return Plan::create([
            'merchant_id' => $merchant->id,
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($merchant, $data['name']),
            'base_price_cents' => $data['base_price_cents'],
            'currency' => $data['currency'] ?? 'INR',
            'billing_cycle' => $data['billing_cycle'],
            'included_units' => $data['included_units'],
            'overage_rate_cents' => $data['overage_rate_cents'],
        ]);
    }

    public function toggleActive(Plan $plan): Plan
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        return $plan;
    }

    private function uniqueSlug(Merchant $merchant, string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (Plan::where('merchant_id', $merchant->id)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
