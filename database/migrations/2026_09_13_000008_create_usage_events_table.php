<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Raw usage log, designed for 50L+ rows. FKs aren't cascading — deletes at this
// volume shouldn't cascade; handle tenant/customer removal at the app layer.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('subscription_id');

            $table->string('event_key')->comment('Caller-supplied idempotency key; unique per merchant so retries never double-count');
            $table->unsignedInteger('units')->default(1)->comment('Usage units this single event represents, e.g. 1 API call');
            $table->date('recorded_date')->comment('Calendar date the usage occurred on (not created_at) — this is what billing periods are measured against');
            $table->json('metadata')->nullable()->comment('e.g. {"endpoint": "/exchange-rate", "from": "USD", "to": "INR"}');

            $table->boolean('is_aggregated')
                ->default(false)
                ->comment('0 = pending, not yet rolled into daily_usage | 1 = aggregated, safe to ignore on reruns');

            $table->timestamp('created_at')->useCurrent();

            // Idempotency guarantee for POST /usage.
            $table->unique(['merchant_id', 'event_key'], 'usage_events_merchant_event_unique');
            $table->index(['merchant_id', 'customer_id', 'recorded_date', 'is_aggregated'], 'usage_events_aggregation_idx');
            $table->index(['subscription_id', 'recorded_date'], 'usage_events_subscription_date_idx');
            $table->index('created_at', 'usage_events_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_events');
    }
};
