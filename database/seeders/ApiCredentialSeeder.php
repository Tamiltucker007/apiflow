<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Merchant;
use App\Services\ApiCredentialService;
use Illuminate\Database\Seeder;

class ApiCredentialSeeder extends Seeder
{
    public function run(): void
    {
        $finpay = Merchant::where('slug', 'finpay')->firstOrFail();
        $credentials = new ApiCredentialService;

        $rows = Customer::where('merchant_id', $finpay->id)->get()->map(function (Customer $customer) use ($credentials) {
            $result = $credentials->generateKey($customer, 'Seeded demo key');

            return [$customer->name, $result['key']];
        });

        $this->command->warn('API keys shown once — for local testing only:');
        $this->command->table(['Customer', 'API Key'], $rows);
    }
}
