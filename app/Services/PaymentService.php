<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Str;

// Stands in for a real payment gateway call (Stripe PaymentIntent create +
// confirm, normally followed by a webhook to confirm settlement). No Stripe
// integration exists yet (see README) — this simulates the request/response
// round trip so the rest of the app (status transitions, UI) is already
// wired up correctly for when a real gateway replaces this method's body.
class PaymentService
{
    /**
     * @return array{success: bool, reference: string, message: string}
     */
    public function charge(Invoice $invoice): array
    {
        // TODO: Replace with a real Stripe PaymentIntent create+confirm call,
        // and move the resulting status update to a webhook handler instead
        // of trusting this synchronous "response".
        return [
            'success' => true,
            'reference' => 'sim_'.Str::random(16),
            'message' => 'Payment simulated successfully.',
        ];
    }
}
