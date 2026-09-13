<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Audit trail of mid-cycle plan changes, used to split billing into per-plan segments.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plan_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('old_plan_id')->constrained('plans')->restrictOnDelete();
            $table->foreignId('new_plan_id')->constrained('plans')->restrictOnDelete();

            $table->timestamp('changed_at')->comment('Exact moment the change was made (audit purposes)');
            $table->date('effective_date')->comment('Calendar date the new plan starts billing from — the segment boundary');

            $table->timestamps();

            $table->index(['subscription_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plan_changes');
    }
};
