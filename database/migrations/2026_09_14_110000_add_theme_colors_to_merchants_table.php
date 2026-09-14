<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Per-merchant branding for the public registration flow (hero gradient +
// primary button). Plain hex strings, not a Tailwind class name: Tailwind's
// compiler only generates CSS for class names it can see in source files at
// build time, so a runtime value from the database can never resolve to a
// utility class — these are applied as inline CSS custom properties instead
// (see resources/views/portal/register.blade.php).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('theme_from', 7)->nullable()->after('description')->comment('Hex gradient start, e.g. #4f46e5');
            $table->string('theme_to', 7)->nullable()->after('theme_from')->comment('Hex gradient end, e.g. #c026d3');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['theme_from', 'theme_to']);
        });
    }
};
