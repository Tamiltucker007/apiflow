<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// One row per customer/subscription/day; billing and dashboard read this, not usage_events.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_usage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('subscription_id');
            $table->date('usage_date');
            $table->unsignedInteger('total_units')->default(0)->comment('Sum of usage_events.units for this customer/subscription/date');
            $table->timestamps();

            // Lets AggregateUsageJob upsert atomically instead of read-then-write.
            $table->unique(['merchant_id', 'customer_id', 'subscription_id', 'usage_date'], 'daily_usage_upsert_unique');
            $table->index(['subscription_id', 'usage_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_usage');
    }
};
