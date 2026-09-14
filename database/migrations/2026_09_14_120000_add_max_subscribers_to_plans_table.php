<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Null = unlimited. Set, it caps how many active subscriptions a
        // plan can carry — used for the 90%-full alert and edit guard.
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('max_subscribers')->nullable()->after('included_units');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('max_subscribers');
        });
    }
};
