<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // Order matters: MerchantUserSeeder looks up the merchant MerchantSeeder creates.
    public function run(): void
    {
        $this->call([
            SuperAdminSeeder::class,
            MerchantSeeder::class,
            MerchantUserSeeder::class,
        ]);

        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Super Admin', 'admin@apiflow.com', 'password'],
                ['Merchant Admin (FinPay)', 'admin@finpay.com', 'password'],
                ['Merchant Staff (FinPay)', 'staff@finpay.com', 'password'],
            ]
        );
    }
}
