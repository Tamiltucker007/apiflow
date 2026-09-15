<?php

namespace Tests\Feature\Portal;

use App\Enums\BillingCycle;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\DailyUsage;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class CustomerPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_log_in_with_a_portal_password(): void
    {
        $customer = Customer::factory()->for(Merchant::factory())->create([
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->post('/login', ['email' => $customer->email, 'password' => 'correct-password']);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_a_customer_cannot_log_in_with_the_wrong_password(): void
    {
        $customer = Customer::factory()->for(Merchant::factory())->create([
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->post('/login', ['email' => $customer->email, 'password' => 'wrong-password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('customer');
    }

    public function test_a_customer_with_no_portal_password_set_cannot_log_in(): void
    {
        $customer = Customer::factory()->for(Merchant::factory())->create(['password' => null]);

        $response = $this->post('/login', ['email' => $customer->email, 'password' => 'anything']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('customer');
    }

    public function test_an_unauthenticated_visitor_is_redirected_to_the_portal_login_not_the_merchant_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_a_customer_can_view_their_own_invoice(): void
    {
        $merchant = Merchant::factory()->create();
        $plan = Plan::factory()->for($merchant)->create(['billing_cycle' => BillingCycle::Monthly]);
        $customer = Customer::factory()->for($merchant)->create(['password' => bcrypt('secret')]);
        $subscription = Subscription::factory()->for($merchant)->create([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'current_period_start' => '2026-01-01',
            'current_period_end' => '2026-01-31',
        ]);
        $invoice = app(BillingService::class)->generateInvoice($subscription);

        $response = $this->actingAs($customer, 'customer')->get(route('invoices.show', $invoice));

        $response->assertOk();
        $response->assertSee($invoice->invoice_number);
    }

    public function test_a_customer_cannot_view_another_customers_invoice(): void
    {
        $merchant = Merchant::factory()->create();
        $plan = Plan::factory()->for($merchant)->create(['billing_cycle' => BillingCycle::Monthly]);

        $owner = Customer::factory()->for($merchant)->create();
        $ownerSubscription = Subscription::factory()->for($merchant)->create([
            'customer_id' => $owner->id,
            'plan_id' => $plan->id,
            'current_period_start' => '2026-01-01',
            'current_period_end' => '2026-01-31',
        ]);
        $invoice = app(BillingService::class)->generateInvoice($ownerSubscription);

        $otherCustomer = Customer::factory()->for($merchant)->create(['password' => bcrypt('secret')]);

        $response = $this->actingAs($otherCustomer, 'customer')->get(route('invoices.show', $invoice));

        $response->assertNotFound();
    }

    public function test_a_customer_can_pay_their_own_pending_invoice(): void
    {
        $merchant = Merchant::factory()->create();
        $plan = Plan::factory()->for($merchant)->create(['billing_cycle' => BillingCycle::Monthly]);
        $customer = Customer::factory()->for($merchant)->create(['password' => bcrypt('secret')]);
        $subscription = Subscription::factory()->for($merchant)->create([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'current_period_start' => '2026-01-01',
            'current_period_end' => '2026-01-31',
        ]);
        $invoice = app(BillingService::class)->generateInvoice($subscription);

        $response = $this->actingAs($customer, 'customer')->post(route('invoices.pay', $invoice));

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('status');
        $invoice->refresh();
        $this->assertSame(\App\Enums\InvoiceStatus::Paid, $invoice->status);
        $this->assertNotNull($invoice->paid_at);
        $this->assertNotNull($invoice->stripe_payment_intent_id);
    }

    public function test_a_customer_cannot_pay_another_customers_invoice(): void
    {
        $merchant = Merchant::factory()->create();
        $plan = Plan::factory()->for($merchant)->create(['billing_cycle' => BillingCycle::Monthly]);
        $owner = Customer::factory()->for($merchant)->create();
        $ownerSubscription = Subscription::factory()->for($merchant)->create([
            'customer_id' => $owner->id,
            'plan_id' => $plan->id,
            'current_period_start' => '2026-01-01',
            'current_period_end' => '2026-01-31',
        ]);
        $invoice = app(BillingService::class)->generateInvoice($ownerSubscription);
        $otherCustomer = Customer::factory()->for($merchant)->create(['password' => bcrypt('secret')]);

        $response = $this->actingAs($otherCustomer, 'customer')->post(route('invoices.pay', $invoice));

        $response->assertNotFound();
        $this->assertSame(\App\Enums\InvoiceStatus::Pending, $invoice->fresh()->status);
    }

    public function test_a_customer_cannot_pay_an_already_paid_invoice(): void
    {
        $merchant = Merchant::factory()->create();
        $plan = Plan::factory()->for($merchant)->create(['billing_cycle' => BillingCycle::Monthly]);
        $customer = Customer::factory()->for($merchant)->create(['password' => bcrypt('secret')]);
        $subscription = Subscription::factory()->for($merchant)->create([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'current_period_start' => '2026-01-01',
            'current_period_end' => '2026-01-31',
        ]);
        $invoice = app(BillingService::class)->generateInvoice($subscription);
        $invoice->update(['status' => \App\Enums\InvoiceStatus::Paid, 'paid_at' => now()]);

        $response = $this->actingAs($customer, 'customer')->post(route('invoices.pay', $invoice));

        $response->assertNotFound();
    }

    public function test_the_dashboard_shows_usage_against_the_active_plan(): void
    {
        $merchant = Merchant::factory()->create();
        $plan = Plan::factory()->for($merchant)->create([
            'name' => 'Growth',
            'included_units' => 1000,
            'billing_cycle' => BillingCycle::Monthly,
        ]);
        $customer = Customer::factory()->for($merchant)->create(['password' => bcrypt('secret')]);
        $subscription = Subscription::factory()->for($merchant)->create([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'current_period_start' => now()->startOfMonth(),
            'current_period_end' => now()->endOfMonth(),
        ]);
        DailyUsage::create([
            'merchant_id' => $merchant->id,
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'usage_date' => now()->toDateString(),
            'total_units' => 400,
        ]);

        $response = $this->actingAs($customer, 'customer')->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Growth');
        $response->assertSee('400');
    }

    public function test_a_customer_can_simulate_their_own_usage(): void
    {
        $merchant = Merchant::factory()->create();
        $plan = Plan::factory()->for($merchant)->create(['billing_cycle' => BillingCycle::Monthly]);
        $customer = Customer::factory()->for($merchant)->create(['password' => bcrypt('secret')]);
        Subscription::factory()->for($merchant)->create([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'current_period_start' => now()->startOfMonth(),
            'current_period_end' => now()->endOfMonth(),
        ]);

        $response = $this->actingAs($customer, 'customer')->post(route('dashboard.simulate-usage'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('status');
        $this->assertTrue(DailyUsage::where('customer_id', $customer->id)->exists());
    }

    public function test_simulating_usage_without_a_subscription_shows_an_error(): void
    {
        $customer = Customer::factory()->for(Merchant::factory())->create(['password' => bcrypt('secret')]);

        $response = $this->actingAs($customer, 'customer')->post(route('dashboard.simulate-usage'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_a_merchant_admin_can_issue_a_portal_password_and_the_customer_can_then_log_in(): void
    {
        $merchant = Merchant::factory()->create();
        $admin = User::factory()->for($merchant)->create(['role' => UserRole::MerchantAdmin]);
        $customer = Customer::factory()->for($merchant)->create(['password' => null]);

        $response = $this->actingAs($admin)
            ->post(route('admin.customers.portal-password.store', [$merchant, $customer]));

        $response->assertRedirect(route('admin.customers.show', [$merchant, $customer]));
        $plaintextPassword = $response->getSession()->get('newPortalPassword');
        $this->assertNotEmpty($plaintextPassword);

        Auth::guard('customer')->logout();
        $loginResponse = $this->post('/login', ['email' => $customer->email, 'password' => $plaintextPassword]);

        $loginResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($customer->fresh(), 'customer');
    }

    public function test_the_customer_show_page_renders_the_portal_access_panel(): void
    {
        $merchant = Merchant::factory()->create();
        $admin = User::factory()->for($merchant)->create(['role' => UserRole::MerchantAdmin]);
        $customer = Customer::factory()->for($merchant)->create(['password' => null]);

        $response = $this->actingAs($admin)->get(route('admin.customers.show', [$merchant, $customer]));

        $response->assertOk();
        $response->assertSee('No password set');
        $response->assertSee('Enable Portal Access');
    }

    public function test_the_dashboard_warns_at_90_percent_usage(): void
    {
        [$customer, $subscription] = $this->subscribedCustomerWithUsage(includedUnits: 1000, usedUnits: 900);

        $response = $this->actingAs($customer, 'customer')->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('90%');
        $response->assertSee('close to the limit');
    }

    public function test_the_subscription_page_shows_plan_details(): void
    {
        [$customer, $subscription] = $this->subscribedCustomerWithUsage(includedUnits: 1000, usedUnits: 400, planName: 'Growth');

        $response = $this->actingAs($customer, 'customer')->get(route('subscription'));

        $response->assertOk();
        $response->assertSee('Growth');
        $response->assertSee('Next Billing Date');
    }

    public function test_a_customer_with_no_active_subscription_is_redirected_away_from_the_subscription_page(): void
    {
        $customer = Customer::factory()->for(Merchant::factory())->create(['password' => bcrypt('secret')]);

        $response = $this->actingAs($customer, 'customer')->get(route('subscription'));

        $response->assertRedirect(route('plans.choose'));
    }

    public function test_a_customer_can_change_their_own_plan(): void
    {
        $merchant = Merchant::factory()->create();
        $currentPlan = Plan::factory()->for($merchant)->create(['billing_cycle' => BillingCycle::Monthly]);
        $newPlan = Plan::factory()->for($merchant)->create(['billing_cycle' => BillingCycle::Monthly]);
        $customer = Customer::factory()->for($merchant)->create(['password' => bcrypt('secret')]);
        $subscription = Subscription::factory()->for($merchant)->create([
            'customer_id' => $customer->id,
            'plan_id' => $currentPlan->id,
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->put(route('subscription.change-plan'), ['plan_id' => $newPlan->id]);

        $response->assertRedirect(route('subscription'));
        $this->assertSame($newPlan->id, $subscription->fresh()->plan_id);
    }

    public function test_a_customer_cannot_change_to_another_merchants_plan(): void
    {
        $merchant = Merchant::factory()->create();
        $currentPlan = Plan::factory()->for($merchant)->create();
        $customer = Customer::factory()->for($merchant)->create(['password' => bcrypt('secret')]);
        $subscription = Subscription::factory()->for($merchant)->create([
            'customer_id' => $customer->id,
            'plan_id' => $currentPlan->id,
        ]);
        $foreignPlan = Plan::factory()->for(Merchant::factory())->create();

        $response = $this->actingAs($customer, 'customer')
            ->put(route('subscription.change-plan'), ['plan_id' => $foreignPlan->id]);

        $response->assertSessionHasErrors('plan_id');
        $this->assertSame($currentPlan->id, $subscription->fresh()->plan_id);
    }

    public function test_a_customer_cannot_switch_to_a_different_billing_cycle_plan(): void
    {
        $merchant = Merchant::factory()->create();
        $monthlyPlan = Plan::factory()->for($merchant)->create(['billing_cycle' => BillingCycle::Monthly]);
        $quarterlyPlan = Plan::factory()->for($merchant)->create(['billing_cycle' => BillingCycle::Quarterly]);
        $customer = Customer::factory()->for($merchant)->create(['password' => bcrypt('secret')]);
        $subscription = Subscription::factory()->for($merchant)->create([
            'customer_id' => $customer->id,
            'plan_id' => $monthlyPlan->id,
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->put(route('subscription.change-plan'), ['plan_id' => $quarterlyPlan->id]);

        $response->assertSessionHasErrors('plan_id');
        $this->assertSame($monthlyPlan->id, $subscription->fresh()->plan_id);
    }

    public function test_the_subscription_history_page_lists_past_and_current_subscriptions(): void
    {
        $merchant = Merchant::factory()->create();
        $oldPlan = Plan::factory()->for($merchant)->create(['name' => 'Starter']);
        $newPlan = Plan::factory()->for($merchant)->create(['name' => 'Growth']);
        $customer = Customer::factory()->for($merchant)->create(['password' => bcrypt('secret')]);

        $cancelled = Subscription::factory()->for($merchant)->create([
            'customer_id' => $customer->id,
            'plan_id' => $oldPlan->id,
            'status' => \App\Enums\SubscriptionStatus::Cancelled,
            'cancelled_at' => now()->subMonth(),
        ]);
        $active = Subscription::factory()->for($merchant)->create([
            'customer_id' => $customer->id,
            'plan_id' => $newPlan->id,
        ]);
        \App\Models\SubscriptionPlanChange::create([
            'subscription_id' => $active->id,
            'old_plan_id' => $oldPlan->id,
            'new_plan_id' => $newPlan->id,
            'changed_at' => now(),
            'effective_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($customer, 'customer')->get(route('subscription.history'));

        $response->assertOk();
        $response->assertSee('Starter');
        $response->assertSee('Growth');
        $response->assertSee('Cancelled');
    }

    public function test_the_usage_page_shows_usage_details_and_trend(): void
    {
        [$customer, $subscription] = $this->subscribedCustomerWithUsage(includedUnits: 1000, usedUnits: 400, planName: 'Growth');

        $response = $this->actingAs($customer, 'customer')->get(route('usage'));

        $response->assertOk();
        $response->assertSee('400');
        $response->assertSee('Daily Usage Trend');
    }

    public function test_a_customer_with_no_active_subscription_is_redirected_away_from_the_usage_page(): void
    {
        $customer = Customer::factory()->for(Merchant::factory())->create(['password' => bcrypt('secret')]);

        $response = $this->actingAs($customer, 'customer')->get(route('usage'));

        $response->assertRedirect(route('plans.choose'));
    }

    public function test_the_invoices_index_page_lists_the_customers_invoices(): void
    {
        $merchant = Merchant::factory()->create();
        $plan = Plan::factory()->for($merchant)->create(['billing_cycle' => BillingCycle::Monthly]);
        $customer = Customer::factory()->for($merchant)->create(['password' => bcrypt('secret')]);
        $subscription = Subscription::factory()->for($merchant)->create([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'current_period_start' => '2026-01-01',
            'current_period_end' => '2026-01-31',
        ]);
        $invoice = app(BillingService::class)->generateInvoice($subscription);

        $response = $this->actingAs($customer, 'customer')->get(route('invoices.index'));

        $response->assertOk();
        $response->assertSee($invoice->invoice_number);
    }

    public function test_the_invoices_index_page_shows_an_empty_state_with_no_invoices(): void
    {
        $customer = Customer::factory()->for(Merchant::factory())->create(['password' => bcrypt('secret')]);

        $response = $this->actingAs($customer, 'customer')->get(route('invoices.index'));

        $response->assertOk();
        $response->assertSee('No invoices yet');
    }

    public function test_the_profile_page_shows_account_details(): void
    {
        $merchant = Merchant::factory()->create(['name' => 'Acme Corp']);
        $customer = Customer::factory()->for($merchant)->create(['password' => bcrypt('secret')]);

        $response = $this->actingAs($customer, 'customer')->get(route('profile'));

        $response->assertOk();
        $response->assertSee($customer->email);
        $response->assertSee('Acme Corp');
    }

    public function test_a_customer_can_change_their_own_password(): void
    {
        $customer = Customer::factory()->for(Merchant::factory())->create(['password' => bcrypt('old-password')]);

        $response = $this->actingAs($customer, 'customer')->put(route('profile.password.update'), [
            'current_password' => 'old-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect(route('profile'));

        Auth::guard('customer')->logout();
        $this->post('/login', ['email' => $customer->email, 'password' => 'new-password-123'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_changing_password_requires_the_correct_current_password(): void
    {
        $customer = Customer::factory()->for(Merchant::factory())->create(['password' => bcrypt('old-password')]);

        $response = $this->actingAs($customer, 'customer')->put(route('profile.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    /**
     * @return array{0: Customer, 1: Subscription}
     */
    private function subscribedCustomerWithUsage(int $includedUnits, int $usedUnits, string $planName = 'Plan'): array
    {
        $merchant = Merchant::factory()->create();
        $plan = Plan::factory()->for($merchant)->create([
            'name' => $planName,
            'included_units' => $includedUnits,
            'billing_cycle' => BillingCycle::Monthly,
        ]);
        $customer = Customer::factory()->for($merchant)->create(['password' => bcrypt('secret')]);
        $subscription = Subscription::factory()->for($merchant)->create([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'current_period_start' => now()->startOfMonth(),
            'current_period_end' => now()->endOfMonth(),
        ]);
        DailyUsage::create([
            'merchant_id' => $merchant->id,
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'usage_date' => now()->toDateString(),
            'total_units' => $usedUnits,
        ]);

        return [$customer, $subscription];
    }
}
