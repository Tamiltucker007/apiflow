<?php

namespace Tests\Feature\Portal;

use App\Enums\BillingCycle;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_landing_page_lists_active_merchants_and_links_to_their_register_page(): void
    {
        $active = Merchant::factory()->create(['slug' => 'finpay', 'name' => 'FinPay Technologies', 'is_active' => true]);
        Merchant::factory()->create(['slug' => 'suspended-co', 'name' => 'Suspended Co', 'is_active' => false]);

        $response = $this->get('/register');

        $response->assertOk();
        $response->assertSee('FinPay Technologies');
        $response->assertSee(route('register.show', $active));
        $response->assertDontSee('Suspended Co');
    }

    public function test_the_register_page_uses_that_merchants_own_theme_colors(): void
    {
        $merchant = Merchant::factory()->create([
            'slug' => 'geolocate',
            'theme_from' => '#059669',
            'theme_to' => '#0d9488',
        ]);

        $response = $this->get(route('register.show', $merchant));

        $response->assertOk();
        $response->assertSee('--brand-from: #059669', false);
        $response->assertSee('--brand-to: #0d9488', false);
    }

    public function test_the_register_page_falls_back_to_the_default_theme_when_a_merchant_has_none_set(): void
    {
        $merchant = Merchant::factory()->create(['slug' => 'no-theme-co', 'theme_from' => null, 'theme_to' => null]);

        $response = $this->get(route('register.show', $merchant));

        $response->assertOk();
        $response->assertSee('--brand-from: #4f46e5', false);
    }

    public function test_a_customer_can_self_register_and_is_sent_to_login(): void
    {
        $merchant = Merchant::factory()->create(['slug' => 'finpay']);

        $response = $this->post('/register/finpay', [
            'email' => 'new-customer@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('login'));

        $customer = Customer::where('email', 'new-customer@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertSame($merchant->id, $customer->merchant_id);
        $this->assertSame('new-customer', $customer->name);
        $this->assertGuest('customer');

        $loginResponse = $this->post('/login', ['email' => $customer->email, 'password' => 'password123']);
        $loginResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_registration_requires_matching_password_confirmation(): void
    {
        $merchant = Merchant::factory()->create(['slug' => 'finpay']);

        $response = $this->post('/register/finpay', [
            'email' => 'new-customer@example.com',
            'password' => 'password123',
            'password_confirmation' => 'does-not-match',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest('customer');
        $this->assertDatabaseMissing('customers', ['email' => 'new-customer@example.com']);
    }

    public function test_the_same_email_cannot_register_twice_under_the_same_merchant(): void
    {
        $merchant = Merchant::factory()->create(['slug' => 'finpay']);
        Customer::factory()->for($merchant)->create(['email' => 'existing@example.com']);

        $response = $this->post('/register/finpay', [
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_the_same_email_can_register_under_a_different_merchant(): void
    {
        $merchantA = Merchant::factory()->create(['slug' => 'finpay']);
        $merchantB = Merchant::factory()->create(['slug' => 'other-co']);
        Customer::factory()->for($merchantA)->create(['email' => 'shared@example.com']);

        $response = $this->post('/register/other-co', [
            'email' => 'shared@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertSame(2, Customer::where('email', 'shared@example.com')->count());
    }

    public function test_an_unknown_merchant_slug_404s(): void
    {
        $response = $this->get('/register/does-not-exist');

        $response->assertNotFound();
    }

    public function test_an_inactive_merchant_404s_on_registration(): void
    {
        Merchant::factory()->create(['slug' => 'inactive-co', 'is_active' => false]);

        $response = $this->get('/register/inactive-co');

        $response->assertNotFound();
    }

    public function test_a_freshly_registered_customer_with_no_subscription_is_redirected_to_choose_a_plan(): void
    {
        $merchant = Merchant::factory()->create();
        $customer = Customer::factory()->for($merchant)->create();

        $response = $this->actingAs($customer, 'customer')->get(route('dashboard'));

        $response->assertRedirect(route('plans.choose'));
    }

    public function test_choosing_a_plan_subscribes_the_customer_and_the_dashboard_then_shows_it(): void
    {
        $merchant = Merchant::factory()->create();
        $plan = Plan::factory()->for($merchant)->create([
            'name' => 'Starter',
            'billing_cycle' => BillingCycle::Monthly,
            'is_active' => true,
        ]);
        $customer = Customer::factory()->for($merchant)->create();

        $chooseResponse = $this->actingAs($customer, 'customer')->get(route('plans.choose'));
        $chooseResponse->assertOk();
        $chooseResponse->assertSee('Starter');

        $subscribeResponse = $this->actingAs($customer, 'customer')
            ->post(route('plans.choose.store'), ['plan_id' => $plan->id]);
        $subscribeResponse->assertRedirect(route('dashboard'));

        $dashboardResponse = $this->actingAs($customer, 'customer')->get(route('dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Starter');
    }

    public function test_an_inactive_plan_cannot_be_subscribed_to(): void
    {
        $merchant = Merchant::factory()->create();
        $inactivePlan = Plan::factory()->for($merchant)->create(['is_active' => false]);
        $customer = Customer::factory()->for($merchant)->create();

        $response = $this->actingAs($customer, 'customer')
            ->get(route('plans.choose'));

        $response->assertDontSee($inactivePlan->name);
    }
}
