<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // Order matters: each seeder depends on records the previous one created.
    public function run(): void
    {
        $this->call([
            MerchantSeeder::class,
            MerchantUserSeeder::class,
            PlanSeeder::class,
            CustomerSeeder::class,
            SubscriptionSeeder::class,
            ApiCredentialSeeder::class,
            UsageEventSeeder::class,
            InvoiceSeeder::class,
        ]);

        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Merchant Admin (FinPay)', 'admin@finpay.com', 'password'],
                ['Merchant Staff (FinPay)', 'staff@finpay.com', 'password'],
            ]
        );
    }
}
