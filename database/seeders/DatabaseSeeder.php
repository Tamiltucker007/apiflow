<?php

namespace Database\Seeders;

use Database\Seeders\Support\DemoData;
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

        $rows = [];

        foreach (DemoData::merchants() as $slug => $data) {
            $rows[] = ["Merchant User ({$data['name']})", $data['admin_email'], 'password'];
        }

        $rows[] = ['Customer Portal (ABC Forex Pvt Ltd, at /login)', 'billing@abcforex.test', 'password'];

        $this->command->table(['Role', 'Email', 'Password'], $rows);

        $this->command->newLine();
        $this->command->info('Self-registration landing page: /register (or a direct merchant link, e.g. /register/finpay)');
    }
}
