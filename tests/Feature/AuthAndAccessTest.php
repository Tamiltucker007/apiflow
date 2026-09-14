<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_log_in_with_correct_credentials(): void
    {
        $merchant = Merchant::factory()->create();
        $user = User::factory()->for($merchant)->create(['password' => bcrypt('correct-password')]);

        $response = $this->post('/admin/login', ['email' => $user->email, 'password' => 'correct-password']);

        $response->assertRedirect(route('admin.dashboard', $merchant));
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_user_cannot_log_in_with_the_wrong_password(): void
    {
        $user = User::factory()->for(Merchant::factory())->create(['password' => bcrypt('correct-password')]);

        $response = $this->post('/admin/login', ['email' => $user->email, 'password' => 'wrong-password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_deactivated_user_is_logged_back_out_immediately(): void
    {
        $user = User::factory()->for(Merchant::factory())->create([
            'password' => bcrypt('correct-password'),
            'is_active' => false,
        ]);

        $response = $this->post('/admin/login', ['email' => $user->email, 'password' => 'correct-password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_merchant_user_can_use_write_routes(): void
    {
        $merchant = Merchant::factory()->create();
        $admin = User::factory()->for($merchant)->create();

        $this->actingAs($admin)
            ->post(route('admin.plans.store', $merchant), [
                'name' => 'Pro', 'base_price_cents' => 10000, 'billing_cycle' => 'monthly',
                'included_units' => 1000, 'overage_rate_cents' => 5,
            ])
            ->assertRedirect(route('admin.plans.index', $merchant));

        $this->assertSame(1, Plan::where('merchant_id', $merchant->id)->count());
    }

    public function test_an_admin_cannot_toggle_a_plan_belonging_to_another_merchant(): void
    {
        $merchantA = Merchant::factory()->create();
        $merchantB = Merchant::factory()->create();
        $admin = User::factory()->for($merchantA)->create();
        $planFromB = Plan::factory()->for($merchantB)->create(['is_active' => true]);

        $this->actingAs($admin)
            ->put(route('admin.plans.toggle', [$merchantA, $planFromB]))
            ->assertNotFound();

        $this->assertTrue($planFromB->fresh()->is_active);
    }

    /**
     * Reproduces a real bug: an expired session hitting a POST route (e.g.
     * clicking Logout after the session died) used to get remembered as the
     * "intended" URL and replayed via GET after the next login — a 405 on
     * any POST/PUT/DELETE-only route like /admin/logout.
     */
    public function test_a_post_route_hit_with_an_expired_session_is_not_replayed_via_get_after_login(): void
    {
        $merchant = Merchant::factory()->create();
        $admin = User::factory()->for($merchant)->create(['password' => bcrypt('correct-password')]);

        $this->post('/admin/logout')
            ->assertRedirect('/admin/login');

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'correct-password'])
            ->assertRedirect(route('admin.dashboard', $merchant));
    }

    public function test_admins_cannot_create_a_subscription_the_route_no_longer_exists(): void
    {
        $merchant = Merchant::factory()->create();
        $admin = User::factory()->for($merchant)->create();
        $customer = Customer::factory()->for($merchant)->create();
        $plan = Plan::factory()->for($merchant)->create();

        $this->actingAs($admin)
            ->post("/admin/merchants/{$merchant->id}/subscriptions", [
                'customer_id' => $customer->id,
                'plan_id' => $plan->id,
            ])
            ->assertStatus(405);

        $this->assertSame(0, Subscription::where('customer_id', $customer->id)->count());
    }

    public function test_a_cancelled_subscription_shows_a_red_badge_in_the_admin_table(): void
    {
        $merchant = Merchant::factory()->create();
        $admin = User::factory()->for($merchant)->create();
        $customer = Customer::factory()->for($merchant)->create();
        $plan = Plan::factory()->for($merchant)->create();
        Subscription::factory()->for($merchant)->for($customer)->for($plan)->create([
            'status' => SubscriptionStatus::Cancelled,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.subscriptions.data', $merchant));

        $response->assertOk();
        $this->assertStringContainsString('bg-red-100', $response->getContent());
        $this->assertStringContainsString('Cancelled', $response->getContent());
    }
}
