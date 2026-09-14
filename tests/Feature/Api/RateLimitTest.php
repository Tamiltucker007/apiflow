<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\ApiCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_121st_request_within_a_minute_is_throttled(): void
    {
        $merchant = Merchant::factory()->create();
        $customer = Customer::factory()->for($merchant)->create();
        $plan = Plan::factory()->for($merchant)->create();
        Subscription::factory()->for($merchant)->for($customer)->create(['plan_id' => $plan->id]);
        $key = app(ApiCredentialService::class)->generateKey($customer)['key'];

        $headers = ['Authorization' => "Bearer {$key}"];
        $payload = fn (int $i) => ['event_key' => "rl-{$i}", 'units' => 1, 'recorded_date' => today()->toDateString()];

        for ($i = 1; $i <= 120; $i++) {
            $this->postJson('/api/v1/usage', $payload($i), $headers)->assertStatus(201);
        }

        $this->postJson('/api/v1/usage', $payload(121), $headers)
            ->assertStatus(429)
            ->assertJson(['error' => 'Rate limit exceeded']);
    }

    public function test_two_different_api_credentials_are_throttled_independently(): void
    {
        $merchant = Merchant::factory()->create();
        $customerA = Customer::factory()->for($merchant)->create();
        $customerB = Customer::factory()->for($merchant)->create();
        $plan = Plan::factory()->for($merchant)->create();
        Subscription::factory()->for($merchant)->for($customerA)->create(['plan_id' => $plan->id]);
        Subscription::factory()->for($merchant)->for($customerB)->create(['plan_id' => $plan->id]);
        $keyA = app(ApiCredentialService::class)->generateKey($customerA)['key'];
        $keyB = app(ApiCredentialService::class)->generateKey($customerB)['key'];

        $payload = fn (int $i) => ['event_key' => "rl-a-{$i}", 'units' => 1, 'recorded_date' => today()->toDateString()];

        for ($i = 1; $i <= 120; $i++) {
            $this->postJson('/api/v1/usage', $payload($i), ['Authorization' => "Bearer {$keyA}"])
                ->assertStatus(201);
        }

        // A is now exhausted, but B's own limit is untouched.
        $this->postJson('/api/v1/usage', $payload(121), ['Authorization' => "Bearer {$keyA}"])
            ->assertStatus(429);
        $this->postJson('/api/v1/usage', ['event_key' => 'rl-b-1', 'units' => 1, 'recorded_date' => today()->toDateString()], ['Authorization' => "Bearer {$keyB}"])
            ->assertStatus(201);
    }
}
