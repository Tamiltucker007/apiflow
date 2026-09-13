<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// API keys for customers to call the usage API. Only the SHA-256 hash is stored.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();

            $table->string('key_prefix', 16)->comment('First chars of the key shown in the UI for identification, e.g. "af_live_"');
            $table->string('key_hash', 64)->unique()->comment('SHA-256 hex digest of the full key — the plaintext is never stored');

            $table->string('name')->nullable()->comment('Merchant-chosen label, e.g. "Production Key"');

            $table->boolean('is_active')
                ->default(true)
                ->comment('0 = revoked, rejected by AuthenticateApiKey middleware | 1 = active');

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'customer_id', 'is_active']);
            $table->index('key_prefix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_credentials');
    }
};
