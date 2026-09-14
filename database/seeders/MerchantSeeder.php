<?php

namespace Database\Seeders;

use App\Models\Merchant;
use Database\Seeders\Support\DemoData;
use Illuminate\Database\Seeder;

// Demo tenants used across the seeded data — see DemoData for the actual
// per-merchant definitions every other seeder in this pipeline reads from.
class MerchantSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DemoData::merchants() as $slug => $data) {
            Merchant::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'theme_from' => $data['theme_from'],
                'theme_to' => $data['theme_to'],
                'slug' => $slug,
                'email' => $data['email'],
                'is_active' => true,
            ]);
        }
    }
}
