<?php

use App\Enums\InvoiceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// One invoice per subscription per billing period; idempotency_key prevents duplicates.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->date('period_start');
            $table->date('period_end');

            $table->integer('base_amount_cents')->comment('Sum of prorated base charges across all billing segments, in integer cents');
            $table->unsignedInteger('overage_units')->default(0)->comment('Total usage units billed beyond the included allowance');
            $table->integer('overage_amount_cents')->default(0);
            $table->integer('total_amount_cents')->comment('base_amount_cents + overage_amount_cents (+/- proration adjustments)');

            $table->char('currency', 3);

            $table->string('status')
                ->default(InvoiceStatus::Draft->value)
                ->comment('draft | pending | paid | failed — see App\Enums\InvoiceStatus');

            $table->timestamp('issued_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();

            $table->string('idempotency_key')->unique()->comment('"sub_{subscription_id}_period_{start}_{end}" — prevents duplicate invoices for the same billing period');

            $table->timestamps();

            $table->index(['merchant_id', 'customer_id']);
            $table->index(['subscription_id', 'period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
