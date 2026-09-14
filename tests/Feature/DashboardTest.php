<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\DailyUsage;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_merchant_user_sees_their_own_dashboard(): void
    {
        $merchant = Merchant::factory()->create();
        $admin = User::factory()->for($merchant)->create(['role' => UserRole::MerchantAdmin]);
        $plan = Plan::factory()->for($merchant)->create(['included_units' => 1000]);
        $customer = Customer::factory()->for($merchant)->create();
        $subscription = Subscription::factory()->for($merchant)->for($customer)->create([
            'plan_id' => $plan->id,
            'current_period_start' => now()->startOfMonth(),
            'current_period_end' => now()->endOfMonth()->startOfDay(),
        ]);

        DailyUsage::create([
            'merchant_id' => $merchant->id,
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'usage_date' => now()->toDateString(),
            'total_units' => 250,
        ]);

        $response = $this->actingAs($admin)->get(route('merchants.dashboard', $merchant));

        $response->assertOk();
        $response->assertViewHas('usage', fn ($usage) => $usage['total_units'] === 250 && $usage['included_units'] === 1000);
    }

    public function test_a_user_cannot_view_another_merchants_dashboard(): void
    {
        $ownMerchant = Merchant::factory()->create();
        $otherMerchant = Merchant::factory()->create();
        $user = User::factory()->for($ownMerchant)->create();

        $this->actingAs($user)
            ->get(route('merchants.dashboard', $otherMerchant))
            ->assertForbidden();
    }

    public function test_an_unauthenticated_visitor_is_redirected_to_login(): void
    {
        $merchant = Merchant::factory()->create();

        $this->get(route('merchants.dashboard', $merchant))
            ->assertRedirect(route('login'));
    }
}
