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

        $response = $this->post('/portal/login', ['email' => $customer->email, 'password' => 'correct-password']);

        $response->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_a_customer_cannot_log_in_with_the_wrong_password(): void
    {
        $customer = Customer::factory()->for(Merchant::factory())->create([
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->post('/portal/login', ['email' => $customer->email, 'password' => 'wrong-password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('customer');
    }

    public function test_a_customer_with_no_portal_password_set_cannot_log_in(): void
    {
        $customer = Customer::factory()->for(Merchant::factory())->create(['password' => null]);

        $response = $this->post('/portal/login', ['email' => $customer->email, 'password' => 'anything']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('customer');
    }

    public function test_an_unauthenticated_visitor_is_redirected_to_the_portal_login_not_the_merchant_login(): void
    {
        $response = $this->get('/portal/dashboard');

        $response->assertRedirect(route('portal.login'));
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

        $response = $this->actingAs($customer, 'customer')->get(route('portal.invoices.show', $invoice));

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

        $response = $this->actingAs($otherCustomer, 'customer')->get(route('portal.invoices.show', $invoice));

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

        $response = $this->actingAs($customer, 'customer')->get(route('portal.dashboard'));

        $response->assertOk();
        $response->assertSee('Growth');
        $response->assertSee('400');
    }

    public function test_a_merchant_admin_can_issue_a_portal_password_and_the_customer_can_then_log_in(): void
    {
        $merchant = Merchant::factory()->create();
        $admin = User::factory()->for($merchant)->create(['role' => UserRole::MerchantAdmin]);
        $customer = Customer::factory()->for($merchant)->create(['password' => null]);

        $response = $this->actingAs($admin)
            ->post(route('merchants.customers.portal-password.store', [$merchant, $customer]));

        $response->assertRedirect(route('merchants.customers.show', [$merchant, $customer]));
        $plaintextPassword = $response->getSession()->get('newPortalPassword');
        $this->assertNotEmpty($plaintextPassword);

        Auth::guard('customer')->logout();
        $loginResponse = $this->post('/portal/login', ['email' => $customer->email, 'password' => $plaintextPassword]);

        $loginResponse->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticatedAs($customer->fresh(), 'customer');
    }

    public function test_the_customer_show_page_renders_the_portal_access_panel(): void
    {
        $merchant = Merchant::factory()->create();
        $admin = User::factory()->for($merchant)->create(['role' => UserRole::MerchantAdmin]);
        $customer = Customer::factory()->for($merchant)->create(['password' => null]);

        $response = $this->actingAs($admin)->get(route('merchants.customers.show', [$merchant, $customer]));

        $response->assertOk();
        $response->assertSee('No password set');
        $response->assertSee('Enable Portal Access');
    }
}
