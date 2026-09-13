<?php

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Links a customer to a plan for a billing period; plan_id is always the current plan.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();

            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();

            $table->string('status')
                ->default(SubscriptionStatus::Active->value)
                ->comment('active | cancelled | expired — see App\Enums\SubscriptionStatus');

            $table->date('current_period_start');
            $table->date('current_period_end');
            $table->timestamp('started_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'customer_id', 'status']);
            $table->index('current_period_end');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
