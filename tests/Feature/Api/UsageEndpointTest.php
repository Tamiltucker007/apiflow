<?php

namespace Tests\Feature\Api;

use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageEvent;
use App\Services\ApiCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function issueKeyFor(Customer $customer): string
    {
        return app(ApiCredentialService::class)->generateKey($customer)['key'];
    }

    private function makeSubscribedCustomer(): Customer
    {
        $merchant = Merchant::factory()->create();
        $customer = Customer::factory()->for($merchant)->create();
        $plan = Plan::factory()->for($merchant)->create();
        Subscription::factory()->for($merchant)->for($customer)->create(['plan_id' => $plan->id]);

        return $customer;
    }

    public function test_a_request_without_an_api_key_is_rejected(): void
    {
        $this->postJson('/api/v1/usage', ['event_key' => 'evt-1', 'units' => 1, 'recorded_date' => today()->toDateString()])
            ->assertStatus(401)
            ->assertJson(['error' => 'Invalid or revoked API key.']);
    }

    public function test_a_request_with_an_invalid_api_key_is_rejected(): void
    {
        $this->postJson('/api/v1/usage', ['event_key' => 'evt-1', 'units' => 1, 'recorded_date' => today()->toDateString()], [
            'Authorization' => 'Bearer not-a-real-key',
        ])->assertStatus(401);
    }

    public function test_a_valid_request_records_a_usage_event(): void
    {
        $customer = $this->makeSubscribedCustomer();
        $key = $this->issueKeyFor($customer);

        $response = $this->postJson('/api/v1/usage', [
            'event_key' => 'evt-100',
            'units' => 5,
            'recorded_date' => today()->toDateString(),
        ], ['Authorization' => "Bearer {$key}"]);

        $response->assertStatus(201);
        $this->assertTrue($response->json('created'));
        $this->assertSame(1, UsageEvent::where('event_key', 'evt-100')->count());
    }

    public function test_replaying_the_same_event_key_is_a_no_op_not_a_duplicate(): void
    {
        $customer = $this->makeSubscribedCustomer();
        $key = $this->issueKeyFor($customer);
        $payload = ['event_key' => 'evt-dupe', 'units' => 5, 'recorded_date' => today()->toDateString()];

        $first = $this->postJson('/api/v1/usage', $payload, ['Authorization' => "Bearer {$key}"]);
        $second = $this->postJson('/api/v1/usage', $payload, ['Authorization' => "Bearer {$key}"]);

        $first->assertStatus(201);
        $second->assertStatus(200);
        $this->assertFalse($second->json('created'));
        $this->assertSame(1, UsageEvent::where('event_key', 'evt-dupe')->count());
    }

    public function test_a_customer_with_no_active_subscription_cannot_record_usage(): void
    {
        $merchant = Merchant::factory()->create();
        $customer = Customer::factory()->for($merchant)->create();
        $plan = Plan::factory()->for($merchant)->create();
        Subscription::factory()->for($merchant)->for($customer)->create([
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Cancelled,
        ]);
        $key = $this->issueKeyFor($customer);

        $this->postJson('/api/v1/usage', [
            'event_key' => 'evt-200', 'units' => 1, 'recorded_date' => today()->toDateString(),
        ], ['Authorization' => "Bearer {$key}"])
            ->assertStatus(422)
            ->assertJsonValidationErrors('customer');
    }

    public function test_missing_required_fields_are_rejected_with_validation_errors(): void
    {
        $customer = $this->makeSubscribedCustomer();
        $key = $this->issueKeyFor($customer);

        $this->postJson('/api/v1/usage', [], ['Authorization' => "Bearer {$key}"])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['event_key', 'units', 'recorded_date']);
    }

    public function test_a_future_recorded_date_is_rejected(): void
    {
        $customer = $this->makeSubscribedCustomer();
        $key = $this->issueKeyFor($customer);

        $this->postJson('/api/v1/usage', [
            'event_key' => 'evt-300', 'units' => 1, 'recorded_date' => today()->addDay()->toDateString(),
        ], ['Authorization' => "Bearer {$key}"])
            ->assertStatus(422)
            ->assertJsonValidationErrors('recorded_date');
    }
}
