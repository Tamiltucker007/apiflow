<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Database\Seeder;

// Generates a real invoice per subscription via BillingService, for its
// current (in-progress) period — so proration/overage is visible immediately
// without waiting for a real cycle to end.
class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $finpay = Merchant::where('slug', 'finpay')->firstOrFail();
        $billing = app(BillingService::class);

        Subscription::where('merchant_id', $finpay->id)->active()->get()
            ->each(fn (Subscription $subscription) => $billing->generateInvoice($subscription));
    }
}
