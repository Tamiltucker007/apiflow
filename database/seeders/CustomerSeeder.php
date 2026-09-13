<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Services\CustomerService;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $finpay = Merchant::where('slug', 'finpay')->firstOrFail();
        $customers = new CustomerService;

        foreach ([
            ['name' => 'ABC Forex Pvt Ltd', 'email' => 'billing@abcforex.test'],
            ['name' => 'Beta Retail Pvt Ltd', 'email' => 'billing@betaretail.test'],
            ['name' => 'Craft Foods Co.', 'email' => 'billing@craftfoods.test'],
            ['name' => 'Nova Traders', 'email' => 'billing@novatraders.test'],
            ['name' => 'QuickMart', 'email' => 'billing@quickmart.test'],
        ] as $data) {
            $customers->create($finpay, $data);
        }
    }
}
