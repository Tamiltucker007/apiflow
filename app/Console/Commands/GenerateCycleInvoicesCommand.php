<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Jobs\GenerateInvoiceJob;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class GenerateCycleInvoicesCommand extends Command
{
    protected $signature = 'billing:generate-invoices';

    protected $description = 'Invoice every active subscription whose billing period has ended, then roll it to the next period';

    public function handle(SubscriptionService $subscriptions): int
    {
        $due = Subscription::where('status', SubscriptionStatus::Active)
            ->where('current_period_end', '<=', today())
            ->get();

        foreach ($due as $subscription) {
            // Capture the ending period before advancing, or the queued job
            // would bill whatever period the subscription holds by the time
            // a worker picks it up.
            GenerateInvoiceJob::dispatch(
                $subscription->id,
                $subscription->current_period_start->toDateString(),
                $subscription->current_period_end->toDateString(),
            );

            $subscriptions->advanceToNextPeriod($subscription);
        }

        $this->info("Dispatched invoices for {$due->count()} subscription(s).");

        return self::SUCCESS;
    }
}
