<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adds role + tenant scoping to the default users table.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')
                ->default(UserRole::MerchantStaff->value)
                ->after('password')
                ->comment('merchant_admin = manages own merchant | merchant_staff = read-only + limited write within own merchant');

            $table->foreignId('merchant_id')
                ->after('role')
                ->constrained('merchants')
                ->cascadeOnDelete()
                ->comment('Tenant this user belongs to — every user belongs to exactly one merchant');

            $table->boolean('is_active')
                ->default(true)
                ->after('merchant_id')
                ->comment('0 = deactivated, cannot log in | 1 = active');

            $table->index(['merchant_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['merchant_id']);
            $table->dropIndex(['merchant_id', 'role']);
            $table->dropColumn(['role', 'merchant_id', 'is_active']);
        });
    }
};
