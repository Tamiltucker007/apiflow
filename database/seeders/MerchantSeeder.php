<?php

namespace Database\Seeders;

use App\Models\Merchant;
use Illuminate\Database\Seeder;

// Demo tenant used across the seeded data.
class MerchantSeeder extends Seeder
{
    public function run(): void
    {
        Merchant::create([
            'name' => 'FinPay Technologies',
            'slug' => 'finpay',
            'email' => 'ops@finpay.test',
            'is_active' => true,
        ]);
    }
}
