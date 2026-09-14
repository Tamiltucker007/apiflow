<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Deletion is soft: subscriptions, invoices, and API credentials
            // all reference customer_id, and a hard delete would cascade
            // away real billing history.
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
