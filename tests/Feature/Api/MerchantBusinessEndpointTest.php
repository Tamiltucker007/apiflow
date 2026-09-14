<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\ApiCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// /exchange-rate, /geocode, and /weather are each one merchant's own flavor
// of business API (FinPay/GeoLocate/WeatherCloud) — a customer of any other
// merchant gets 403, not just whichever one happens to hold a valid key.
class MerchantBusinessEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function subscribedCustomerFor(string $slug): Customer
    {
        $merchant = Merchant::factory()->create(['slug' => $slug]);
        $customer = Customer::factory()->for($merchant)->create();
        $plan = Plan::factory()->for($merchant)->create();
        Subscription::factory()->for($merchant)->for($customer)->create(['plan_id' => $plan->id]);

        return $customer;
    }

    private function keyFor(Customer $customer): string
    {
        return app(ApiCredentialService::class)->generateKey($customer)['key'];
    }

    public function test_a_geolocate_customer_can_call_geocode(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '12.9716', 'lon' => '77.5946', 'display_name' => 'Bengaluru, India'],
            ]),
        ]);

        $customer = $this->subscribedCustomerFor('geolocate');
        $key = $this->keyFor($customer);

        $this->getJson('/api/v1/geocode?address=Bengaluru', ['Authorization' => "Bearer {$key}"])
            ->assertOk()
            ->assertJson(['lat' => 12.9716, 'lon' => 77.5946]);
    }

    public function test_a_non_geolocate_customer_cannot_call_geocode(): void
    {
        $customer = $this->subscribedCustomerFor('finpay');
        $key = $this->keyFor($customer);

        $this->getJson('/api/v1/geocode?address=Bengaluru', ['Authorization' => "Bearer {$key}"])
            ->assertStatus(403);
    }

    public function test_a_weathercloud_customer_can_call_weather(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'current_weather' => ['temperature' => 28.5, 'windspeed' => 10.2],
            ]),
        ]);

        $customer = $this->subscribedCustomerFor('weathercloud');
        $key = $this->keyFor($customer);

        $this->getJson('/api/v1/weather?lat=12.97&lon=77.59', ['Authorization' => "Bearer {$key}"])
            ->assertOk()
            ->assertJson(['temperature' => 28.5]);
    }

    public function test_a_non_weathercloud_customer_cannot_call_weather(): void
    {
        $customer = $this->subscribedCustomerFor('geolocate');
        $key = $this->keyFor($customer);

        $this->getJson('/api/v1/weather?lat=12.97&lon=77.59', ['Authorization' => "Bearer {$key}"])
            ->assertStatus(403);
    }
}
