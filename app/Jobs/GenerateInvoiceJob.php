<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// Period dates are passed explicitly (not re-read from the subscription)
// because billing:generate-invoices advances the subscription to its next
// period right after dispatching this job — by the time a queue worker
// picks it up, the subscription's current_period_* would already be wrong.
class GenerateInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(
        private int $subscriptionId,
        private string $periodStart,
        private string $periodEnd,
    ) {}

    public function handle(BillingService $billing): void
    {
        $subscription = Subscription::findOrFail($this->subscriptionId);

        $billing->generateInvoice($subscription, Carbon::parse($this->periodStart), Carbon::parse($this->periodEnd));
    }
}
