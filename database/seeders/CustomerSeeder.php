<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Services\CustomerService;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = new CustomerService;

        foreach (DemoData::merchants() as $slug => $data) {
            $merchant = Merchant::where('slug', $slug)->firstOrFail();

            foreach ($data['customers'] as $customerData) {
                $customer = $customers->create($merchant, $customerData);

                // Gives the demo a ready-made customer-portal login without
                // scripting the "admin issues a password" flow first.
                if (! empty($customerData['portal_password'])) {
                    $customer->update(['password' => $customerData['portal_password']]);
                }
            }
        }
    }
}
