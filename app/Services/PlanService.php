<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\Plan;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

    public function update(Plan $plan, array $data): Plan
    {
        $plan->update([
            'name' => $data['name'],
            'base_price_cents' => $data['base_price_cents'],
            'currency' => $data['currency'] ?? $plan->currency,
            'billing_cycle' => $data['billing_cycle'],
            'included_units' => $data['included_units'],
            'overage_rate_cents' => $data['overage_rate_cents'],
        ]);

        return $plan;
    }

    public function toggleActive(Plan $plan): Plan
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        return $plan;
    }

    /**
     * Soft-deletes a plan. Refuses when any subscription (active or past)
     * still references it — deleting it would break billing history and
     * historical invoice line items. Deactivate is the right tool for
     * "stop new signups"; delete is only for a plan nothing ever used.
     */
    public function delete(Plan $plan): void
    {
        if ($plan->subscriptions()->exists()) {
            throw ValidationException::withMessages([
                'plan' => 'This plan has subscriptions attached to it and cannot be deleted. Deactivate it instead.',
            ]);
        }

        $plan->delete();
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
