<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A merchant's own customers, distinct from App\Models\User (dashboard logins).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('external_id')->nullable()->comment("Merchant's own identifier for this customer, if they have one");
            $table->json('metadata')->nullable()->comment('Free-form merchant-supplied data, e.g. {"tier": "enterprise"}');
            $table->timestamps();

            // Unique per merchant, not globally.
            $table->unique(['merchant_id', 'email']);
            $table->index('merchant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
