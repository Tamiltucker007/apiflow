<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\User;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

// Depends on MerchantSeeder having already run.
class MerchantUserSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DemoData::merchants() as $slug => $data) {
            $merchant = Merchant::where('slug', $slug)->firstOrFail();

            User::factory()->create([
                'name' => "{$data['name']} Admin",
                'email' => $data['admin_email'],
                'merchant_id' => $merchant->id,
            ]);
        }
    }
}
