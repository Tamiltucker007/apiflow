<?php

namespace App\Services;

use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Models\DailyUsage;
use App\Models\Invoice;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BillingService
{
    public function __construct(
        private ProrationService $proration,
        private PlanPricingService $pricing,
    ) {}

    /**
     * Generates (or returns the existing) invoice for a subscription's
     * billing period. $periodStart/$periodEnd default to the subscription's
     * current period, but must be passed explicitly by callers that also
     * advance the subscription (see billing:generate-invoices) so the
     * invoice always reflects the period that actually ended, not whatever
     * the subscription's period fields hold by the time this runs.
     */
    public function generateInvoice(Subscription $subscription, ?Carbon $periodStart = null, ?Carbon $periodEnd = null): Invoice
    {
        $periodStart ??= $subscription->current_period_start;
        $periodEnd ??= $subscription->current_period_end;

        $idempotencyKey = "sub_{$subscription->id}_period_{$periodStart->toDateString()}_{$periodEnd->toDateString()}";

        $existing = Invoice::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing->load('items');
        }

        $segments = $this->proration->calculateSegments($subscription, $periodStart, $periodEnd);
        $multiSegment = count($segments) > 1;

        // The nominal full cycle length, not the (possibly short) billed
        // period — period_end is always a true calendar boundary by
        // construction (see SubscriptionService), even when period_start
        // is shortened by a mid-cycle join.
        $cycle = $subscription->plan->billing_cycle;
        $totalCycleDays = $cycle->periodStart($periodEnd)->diffInDays($cycle->periodEnd($periodEnd)) + 1;

        $items = [];
        $totalCents = 0;
        $totalOverageUnits = 0;
        $totalOverageCents = 0;
        $totalBaseCents = 0;
        $currency = null;

        foreach ($segments as $segment) {
            $plan = $this->pricing->getCachedPlan($segment['plan_id']);
            $currency = $plan->currency;

            $segmentDays = $segment['start']->diffInDays($segment['end']) + 1;
            $proratedBase = $this->proration->prorate($plan->base_price_cents, $segmentDays, $totalCycleDays);
            $proratedIncluded = $this->proration->prorate($plan->included_units, $segmentDays, $totalCycleDays);

            $segmentUsage = (int) DailyUsage::where('subscription_id', $subscription->id)
                ->whereBetween('usage_date', [$segment['start']->toDateString(), $segment['end']->toDateString()])
                ->sum('total_units');

            $label = $multiSegment
                ? "{$plan->name} Plan ({$segment['start']->format('M j')}-{$segment['end']->format('j')})"
                : "{$plan->name} Plan";

            $items[] = [
                'description' => $label,
                'type' => $multiSegment ? InvoiceItemType::ProrationCharge : InvoiceItemType::BaseCharge,
                'plan_id' => $plan->id,
                'amount_cents' => $proratedBase,
                'period_start' => $segment['start']->toDateString(),
                'period_end' => $segment['end']->toDateString(),
            ];
            $totalBaseCents += $proratedBase;
            $totalCents += $proratedBase;

            $overageUnits = max(0, $segmentUsage - $proratedIncluded);

            if ($overageUnits > 0) {
                $overageAmount = $overageUnits * $plan->overage_rate_cents;

                $items[] = [
                    'description' => "{$plan->name} Overage ({$overageUnits} units)",
                    'type' => InvoiceItemType::Overage,
                    'plan_id' => $plan->id,
                    'units' => $overageUnits,
                    'unit_price_cents' => $plan->overage_rate_cents,
                    'amount_cents' => $overageAmount,
                    'period_start' => $segment['start']->toDateString(),
                    'period_end' => $segment['end']->toDateString(),
                ];
                $totalOverageUnits += $overageUnits;
                $totalOverageCents += $overageAmount;
                $totalCents += $overageAmount;
            }
        }

        return DB::transaction(function () use ($subscription, $periodStart, $periodEnd, $items, $totalCents, $totalOverageUnits, $totalOverageCents, $totalBaseCents, $currency, $idempotencyKey) {
            $invoice = Invoice::create([
                'merchant_id' => $subscription->merchant_id,
                'customer_id' => $subscription->customer_id,
                'subscription_id' => $subscription->id,
                'invoice_number' => 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'base_amount_cents' => $totalBaseCents,
                'overage_units' => $totalOverageUnits,
                'overage_amount_cents' => $totalOverageCents,
                'total_amount_cents' => $totalCents,
                'currency' => $currency,
                'status' => InvoiceStatus::Pending,
                'issued_at' => now(),
                'idempotency_key' => $idempotencyKey,
            ]);

            $invoice->items()->createMany($items);

            return $invoice->load('items');
        });
    }
}
