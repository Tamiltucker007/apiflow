<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Database\Seeder;

// Depends on MerchantSeeder having already run.
class MerchantUserSeeder extends Seeder
{
    public function run(): void
    {
        $finpay = Merchant::where('slug', 'finpay')->firstOrFail();

        User::factory()->create([
            'name' => 'FinPay Admin',
            'email' => 'admin@finpay.com',
            'role' => UserRole::MerchantAdmin,
            'merchant_id' => $finpay->id,
        ]);

        User::factory()->create([
            'name' => 'FinPay Staff',
            'email' => 'staff@finpay.com',
            'role' => UserRole::MerchantStaff,
            'merchant_id' => $finpay->id,
        ]);
    }
}
