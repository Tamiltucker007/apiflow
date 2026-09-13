<?php

use App\Enums\BillingCycle;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A merchant's pricing tiers. Money stored as integer cents throughout.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');

            $table->unsignedInteger('base_price_cents')
                ->comment('Monthly/cycle base price in integer cents, e.g. 499900 = Rs 4,999.00');

            $table->char('currency', 3)->default('INR')->comment('ISO 4217 currency code');

            $table->string('billing_cycle')
                ->default(BillingCycle::Monthly->value)
                ->comment('monthly | quarterly | yearly — see App\Enums\BillingCycle');

            $table->unsignedInteger('included_units')->comment('Free usage units per billing cycle before overage applies');
            $table->unsignedInteger('overage_rate_cents')->comment('Cost per usage unit beyond included_units, in integer cents');

            $table->boolean('is_active')
                ->default(true)
                ->comment('0 = retired, cannot be assigned to new subscriptions | 1 = active');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['merchant_id', 'is_active']);
            $table->unique(['merchant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
