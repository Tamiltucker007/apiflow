<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tenants of the platform; other tables scope to merchant_id.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique()->comment('URL-safe identifier, e.g. "finpay"');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')
                ->default(true)
                ->comment('0 = suspended, all API access blocked | 1 = active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
