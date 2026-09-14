<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Nullable: a customer only gets portal login once a merchant admin issues
// them a password (see CustomerService::generatePortalPassword). Until
// then they can still be metered via API key, just can't log into /portal.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('password')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('password');
        });
    }
};
