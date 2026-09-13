<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

// Platform-owner account, not tied to any merchant.
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@apiflow.com',
            'role' => UserRole::SuperAdmin,
            'merchant_id' => null,
        ]);
    }
}
