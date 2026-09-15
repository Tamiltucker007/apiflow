<?php

namespace Tests\Feature;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\ApiCredentialService;
use App\Services\CustomerService;
use App\Services\PlanService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

// Covers the seat-limit, deletion-guard, and deactivation rules around
// plans/customers/subscriptions — each one keeps the system out of an
// inconsistent state (see README "Important Business Rule" section).
class SubscriptionBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_plan_is_flagged_near_its_subscriber_limit_at_90_percent(): void
    {
        $plan = Plan::factory()->create(['max_subscribers' => 10]);
        Subscription::factory()->count(9)->for($plan)->for($plan->merchant)->create();

        $this->assertTrue($plan->isNearSubscriberLimit());
    }

    public function test_a_plan_well_under_its_limit_is_not_flagged(): void
    {
        $plan = Plan::factory()->create(['max_subscribers' => 10]);
        Subscription::factory()->count(3)->for($plan)->for($plan->merchant)->create();

        $this->assertFalse($plan->isNearSubscriberLimit());
    }

    public function test_a_plan_with_no_limit_is_never_flagged(): void
    {
        $plan = Plan::factory()->create(['max_subscribers' => null]);
        Subscription::factory()->count(50)->for($plan)->for($plan->merchant)->create();

        $this->assertFalse($plan->isNearSubscriberLimit());
    }

    public function test_lowering_a_plans_limit_below_its_active_subscriber_count_is_rejected(): void
    {
        $plan = Plan::factory()->create(['max_subscribers' => 10]);
        Subscription::factory()->count(10)->for($plan)->for($plan->merchant)->create();

        $this->expectException(ValidationException::class);

        app(PlanService::class)->update($plan, [
            'name' => $plan->name,
            'base_price_cents' => $plan->base_price_cents,
            'billing_cycle' => $plan->billing_cycle->value,
            'included_units' => $plan->included_units,
            'overage_rate_cents' => $plan->overage_rate_cents,
            'max_subscribers' => 9,
        ]);
    }

    public function test_raising_a_plans_limit_at_its_active_subscriber_count_is_allowed(): void
    {
        $plan = Plan::factory()->create(['max_subscribers' => 10]);
        Subscription::factory()->count(10)->for($plan)->for($plan->merchant)->create();

        $updated = app(PlanService::class)->update($plan, [
            'name' => $plan->name,
            'base_price_cents' => $plan->base_price_cents,
            'billing_cycle' => $plan->billing_cycle->value,
            'included_units' => $plan->included_units,
            'overage_rate_cents' => $plan->overage_rate_cents,
            'max_subscribers' => 10,
        ]);

        $this->assertSame(10, $updated->max_subscribers);
    }

    public function test_a_plan_with_any_subscription_cannot_be_deleted(): void
    {
        $plan = Plan::factory()->create();
        Subscription::factory()->for($plan)->for($plan->merchant)->create(['status' => SubscriptionStatus::Cancelled]);

        $this->expectException(ValidationException::class);

        app(PlanService::class)->delete($plan);
    }

    public function test_deleting_a_customer_with_an_active_subscription_cancels_it_first(): void
    {
        $merchant = Merchant::factory()->create();
        $customer = Customer::factory()->for($merchant)->create();
        $plan = Plan::factory()->for($merchant)->create();
        $subscription = Subscription::factory()->for($plan)->for($customer)->for($merchant)->create();

        app(CustomerService::class)->delete($customer);

        $this->assertSame(SubscriptionStatus::Cancelled, $subscription->fresh()->status);
        $this->assertSoftDeleted($customer);
    }

    public function test_a_deactivated_customer_cannot_log_in_to_the_portal(): void
    {
        $customer = Customer::factory()->create(['password' => 'secret123', 'is_active' => false]);

        $this->post('/login', ['email' => $customer->email, 'password' => 'secret123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('customer');
    }

    public function test_a_deactivated_customers_api_key_is_refused(): void
    {
        $merchant = Merchant::factory()->create();
        $customer = Customer::factory()->for($merchant)->create(['is_active' => false]);
        $key = app(ApiCredentialService::class)->generateKey($customer)['key'];

        $this->postJson('/api/v1/usage', [], ['Authorization' => "Bearer {$key}"])
            ->assertStatus(403)
            ->assertJson(['error' => 'This account has been deactivated.']);
    }

    public function test_cancelling_a_subscription_marks_it_cancelled(): void
    {
        $subscription = Subscription::factory()->create(['status' => SubscriptionStatus::Active]);

        $cancelled = app(SubscriptionService::class)->cancel($subscription);

        $this->assertSame(SubscriptionStatus::Cancelled, $cancelled->status);
        $this->assertNotNull($cancelled->cancelled_at);
    }

    public function test_cancelling_an_already_cancelled_subscription_is_rejected(): void
    {
        $subscription = Subscription::factory()->create(['status' => SubscriptionStatus::Cancelled]);

        $this->expectException(ValidationException::class);

        app(SubscriptionService::class)->cancel($subscription);
    }

    public function test_cancelling_a_subscription_bills_a_final_invoice_for_days_used(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::Active,
            'current_period_start' => now()->startOfMonth(),
            'current_period_end' => now()->endOfMonth(),
        ]);

        app(SubscriptionService::class)->cancel($subscription);

        $invoice = Invoice::where('subscription_id', $subscription->id)->first();
        $this->assertNotNull($invoice);
        $this->assertSame(now()->startOfMonth()->toDateString(), $invoice->period_start->toDateString());
        $this->assertSame(now()->toDateString(), $invoice->period_end->toDateString());
    }

    public function test_cancelling_a_subscription_already_invoiced_this_cycle_does_not_double_bill(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => SubscriptionStatus::Active,
            'current_period_start' => now()->startOfMonth(),
            'current_period_end' => now()->endOfMonth(),
        ]);
        app(\App\Services\BillingService::class)->generateInvoice($subscription);

        app(SubscriptionService::class)->cancel($subscription);

        $this->assertSame(1, Invoice::where('subscription_id', $subscription->id)->count());
    }

    public function test_an_admin_cannot_switch_a_subscription_to_a_different_billing_cycle_plan(): void
    {
        $merchant = Merchant::factory()->create();
        $admin = User::factory()->for($merchant)->create();
        $monthlyPlan = Plan::factory()->for($merchant)->create(['billing_cycle' => BillingCycle::Monthly]);
        $quarterlyPlan = Plan::factory()->for($merchant)->create(['billing_cycle' => BillingCycle::Quarterly]);
        $subscription = Subscription::factory()->for($merchant)->for($monthlyPlan)->create();

        $response = $this->actingAs($admin)
            ->put(route('admin.subscriptions.change-plan', [$merchant, $subscription]), ['plan_id' => $quarterlyPlan->id]);

        $response->assertSessionHasErrors('plan_id');
        $this->assertSame($monthlyPlan->id, $subscription->fresh()->plan_id);
    }
}
