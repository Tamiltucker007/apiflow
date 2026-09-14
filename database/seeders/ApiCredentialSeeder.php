<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Services\ApiCredentialService;
use Illuminate\Database\Seeder;

// Merchant-agnostic by design — every customer, across every seeded
// merchant, gets one demo key. No per-merchant loop needed here since
// Customer already carries its own merchant_id.
class ApiCredentialSeeder extends Seeder
{
    public function run(): void
    {
        $credentials = new ApiCredentialService;

        $rows = Customer::with('merchant')->get()->map(function (Customer $customer) use ($credentials) {
            $result = $credentials->generateKey($customer, 'Seeded demo key');

            return [$customer->merchant->name, $customer->name, $result['key']];
        });

        $this->command->warn('API keys shown once — for local testing only:');
        $this->command->table(['Merchant', 'Customer', 'API Key'], $rows);
    }
}
