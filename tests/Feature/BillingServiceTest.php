<?php

namespace Tests\Feature;

use App\Enums\BillingCycle;
use App\Enums\InvoiceItemType;
use App\Models\DailyUsage;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPlanChange;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function billing(): BillingService
    {
        return app(BillingService::class);
    }

    public function test_a_full_month_subscription_with_no_overage_is_billed_at_the_full_base_price(): void
    {
        $merchant = Merchant::factory()->create();
        $plan = Plan::factory()->for($merchant)->create([
            'base_price_cents' => 100000,
            'included_units' => 1000,
            'overage_rate_cents' => 10,
            'billing_cycle' => BillingCycle::Monthly,
        ]);
        $subscription = Subscription::factory()->for($merchant)->create([
            'plan_id' => $plan->id,
            'current_period_start' => '2026-01-01',
            'current_period_end' => '2026-01-31',
        ]);

        DailyUsage::create([
            'merchant_id' => $merchant->id,
            'customer_id' => $subscription->customer_id,
            'subscription_id' => $subscription->id,
            'usage_date' => '2026-01-10',
            'total_units' => 500,
        ]);

        $invoice = $this->billing()->generateInvoice($subscription);

        $this->assertSame(100000, $invoice->base_amount_cents);
        $this->assertSame(0, $invoice->overage_amount_cents);
        $this->assertSame(100000, $invoice->total_amount_cents);
        $this->assertCount(1, $invoice->items);
    }

    public function test_a_mid_cycle_start_prorates_the_base_charge_and_the_included_allowance(): void
    {
        $merchant = Merchant::factory()->create();
        $plan = Plan::factory()->for($merchant)->create([
            'base_price_cents' => 100000,
            'included_units' => 1000,
            'overage_rate_cents' => 10,
            'billing_cycle' => BillingCycle::Monthly,
        ]);
        // Joined on the 15th: a 17-day period out of a 31-day January.
        $subscription = Subscription::factory()->for($merchant)->create([
            'plan_id' => $plan->id,
            'current_period_start' => '2026-01-15',
            'current_period_end' => '2026-01-31',
        ]);

        DailyUsage::create([
            'merchant_id' => $merchant->id,
            'customer_id' => $subscription->customer_id,
            'subscription_id' => $subscription->id,
            'usage_date' => '2026-01-20',
            'total_units' => 600,
        ]);

        $invoice = $this->billing()->generateInvoice($subscription);

        // base: round(100000 * 17/31) = 54839; included: round(1000*17/31) = 548
        // overage: 600 - 548 = 52 units * 10 cents = 520
        $this->assertSame(54839, $invoice->base_amount_cents);
        $this->assertSame(52, $invoice->overage_units);
        $this->assertSame(520, $invoice->overage_amount_cents);
        $this->assertSame(55359, $invoice->total_amount_cents);
    }

    public function test_a_mid_cycle_plan_change_bills_each_segment_at_its_own_plans_prorated_rate(): void
    {
        $merchant = Merchant::factory()->create();
        $oldPlan = Plan::factory()->for($merchant)->create([
            'base_price_cents' => 100000, 'included_units' => 1000, 'overage_rate_cents' => 10,
            'billing_cycle' => BillingCycle::Monthly,
        ]);
        $newPlan = Plan::factory()->for($merchant)->create([
            'base_price_cents' => 200000, 'included_units' => 2000, 'overage_rate_cents' => 20,
            'billing_cycle' => BillingCycle::Monthly,
        ]);
        $subscription = Subscription::factory()->for($merchant)->create([
            'plan_id' => $newPlan->id,
            'current_period_start' => '2026-01-01',
            'current_period_end' => '2026-01-31',
        ]);

        SubscriptionPlanChange::create([
            'subscription_id' => $subscription->id,
            'old_plan_id' => $oldPlan->id,
            'new_plan_id' => $newPlan->id,
            'changed_at' => now(),
            'effective_date' => '2026-01-16',
        ]);

        // Segment A (old plan, Jan 1-15, 15 days): 300 units, under its 484 allowance.
        DailyUsage::create([
            'merchant_id' => $merchant->id, 'customer_id' => $subscription->customer_id,
            'subscription_id' => $subscription->id, 'usage_date' => '2026-01-05', 'total_units' => 300,
        ]);
        // Segment B (new plan, Jan 16-31, 16 days): 1200 units, over its 1032 allowance by 168.
        DailyUsage::create([
            'merchant_id' => $merchant->id, 'customer_id' => $subscription->customer_id,
            'subscription_id' => $subscription->id, 'usage_date' => '2026-01-20', 'total_units' => 1200,
        ]);

        $invoice = $this->billing()->generateInvoice($subscription);

        // base A: round(100000*15/31) = 48387; base B: round(200000*16/31) = 103226
        $this->assertSame(151613, $invoice->base_amount_cents);
        // included B: round(2000*16/31) = 1032; overage = 1200-1032 = 168 * 20 = 3360
        $this->assertSame(168, $invoice->overage_units);
        $this->assertSame(3360, $invoice->overage_amount_cents);
        $this->assertSame(154973, $invoice->total_amount_cents);

        // Two base/proration items (one per plan segment) plus one overage item.
        $this->assertCount(3, $invoice->items);
        $this->assertSame(2, $invoice->items->where('type', InvoiceItemType::ProrationCharge)->count());
        $this->assertSame(1, $invoice->items->where('type', InvoiceItemType::Overage)->count());
    }

    public function test_generating_an_invoice_twice_for_the_same_period_is_idempotent(): void
    {
        $merchant = Merchant::factory()->create();
        $plan = Plan::factory()->for($merchant)->create(['billing_cycle' => BillingCycle::Monthly]);
        $subscription = Subscription::factory()->for($merchant)->create([
            'plan_id' => $plan->id,
            'current_period_start' => '2026-01-01',
            'current_period_end' => '2026-01-31',
        ]);

        $first = $this->billing()->generateInvoice($subscription);
        $second = $this->billing()->generateInvoice($subscription);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Invoice::count());
    }
}
