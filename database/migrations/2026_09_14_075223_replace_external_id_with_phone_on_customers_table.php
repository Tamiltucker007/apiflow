<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('external_id');
            $table->string('phone')->nullable()->after('email')->comment('Contact number for this customer');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('phone');
            $table->string('external_id')->nullable()->after('email')->comment("Merchant's own identifier for this customer, if they have one");
        });
    }
};
