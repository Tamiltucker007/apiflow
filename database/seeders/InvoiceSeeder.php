<?php

namespace Database\Seeders;

use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Database\Seeder;

// Generates a real invoice per active subscription (across every seeded
// merchant — BillingService already scopes entirely off the subscription
// it's given, so no per-merchant loop is needed here) via BillingService,
// for its current (in-progress) period — so proration/overage is visible
// immediately without waiting for a real cycle to end.
class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $billing = app(BillingService::class);

        Subscription::active()->get()
            ->each(fn (Subscription $subscription) => $billing->generateInvoice($subscription));
    }
}
