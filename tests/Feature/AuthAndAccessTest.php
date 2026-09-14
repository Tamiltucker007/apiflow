<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Merchant;
use App\Models\Plan;
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

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'correct-password']);

        $response->assertRedirect(route('merchants.dashboard', $merchant));
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_user_cannot_log_in_with_the_wrong_password(): void
    {
        $user = User::factory()->for(Merchant::factory())->create(['password' => bcrypt('correct-password')]);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_deactivated_user_is_logged_back_out_immediately(): void
    {
        $user = User::factory()->for(Merchant::factory())->create([
            'password' => bcrypt('correct-password'),
            'is_active' => false,
        ]);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'correct-password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_merchant_staff_is_blocked_from_write_routes(): void
    {
        $merchant = Merchant::factory()->create();
        $staff = User::factory()->for($merchant)->create(['role' => UserRole::MerchantStaff]);

        $this->actingAs($staff)
            ->post(route('merchants.plans.store', $merchant), [
                'name' => 'Pro', 'base_price_cents' => 10000, 'billing_cycle' => 'monthly',
                'included_units' => 1000, 'overage_rate_cents' => 5,
            ])
            ->assertForbidden();

        $this->assertSame(0, Plan::where('merchant_id', $merchant->id)->count());
    }

    public function test_merchant_admin_can_use_write_routes(): void
    {
        $merchant = Merchant::factory()->create();
        $admin = User::factory()->for($merchant)->create(['role' => UserRole::MerchantAdmin]);

        $this->actingAs($admin)
            ->post(route('merchants.plans.store', $merchant), [
                'name' => 'Pro', 'base_price_cents' => 10000, 'billing_cycle' => 'monthly',
                'included_units' => 1000, 'overage_rate_cents' => 5,
            ])
            ->assertRedirect(route('merchants.plans.index', $merchant));

        $this->assertSame(1, Plan::where('merchant_id', $merchant->id)->count());
    }

    public function test_an_admin_cannot_toggle_a_plan_belonging_to_another_merchant(): void
    {
        $merchantA = Merchant::factory()->create();
        $merchantB = Merchant::factory()->create();
        $admin = User::factory()->for($merchantA)->create(['role' => UserRole::MerchantAdmin]);
        $planFromB = Plan::factory()->for($merchantB)->create(['is_active' => true]);

        $this->actingAs($admin)
            ->put(route('merchants.plans.toggle', [$merchantA, $planFromB]))
            ->assertNotFound();

        $this->assertTrue($planFromB->fresh()->is_active);
    }
}
