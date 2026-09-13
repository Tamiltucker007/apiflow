<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\UsageEvent;
use Illuminate\Validation\ValidationException;

class UsageService
{
    /**
     * Idempotent usage recording: insertOrIgnore relies on the
     * unique(merchant_id, event_key) constraint, so a retried request with
     * the same event_key is a safe no-op rather than a duplicate row. The
     * insert count (not a separate exists-check) tells us created vs
     * duplicate, avoiding a check-then-write race under concurrent retries.
     *
     * @return array{event: UsageEvent, created: bool}
     */
    public function recordUsage(Customer $customer, string $eventKey, int $units, string $recordedDate, ?array $metadata = null): array
    {
        $subscription = $customer->activeSubscription;

        if (! $subscription) {
            throw ValidationException::withMessages([
                'customer' => 'This customer has no active subscription.',
            ]);
        }

        $insertedCount = UsageEvent::insertOrIgnore([[
            'merchant_id' => $customer->merchant_id,
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'event_key' => $eventKey,
            'units' => $units,
            'recorded_date' => $recordedDate,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'is_aggregated' => false,
            'created_at' => now(),
        ]]);

        $event = UsageEvent::where('merchant_id', $customer->merchant_id)
            ->where('event_key', $eventKey)
            ->firstOrFail();

        return ['event' => $event, 'created' => $insertedCount > 0];
    }
}
