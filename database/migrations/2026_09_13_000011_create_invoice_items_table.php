<?php

use App\Enums\InvoiceItemType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Line items making up an invoice's total; split by segment for mid-cycle plan changes.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('description')->comment('Human-readable line, e.g. "Growth Plan (Sep 1-14)"');

            $table->string('type')
                ->comment('base_charge | overage | proration_credit | proration_charge — see App\Enums\InvoiceItemType');

            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete()->comment('Which plan this charge was calculated against');
            $table->integer('units')->nullable()->comment('Usage units this line covers (overage items only)');
            $table->integer('unit_price_cents')->nullable();
            $table->integer('amount_cents')->comment('Line total in integer cents');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
