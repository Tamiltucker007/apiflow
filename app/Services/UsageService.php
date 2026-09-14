<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\UsageEvent;
use Carbon\Carbon;
use Illuminate\Support\Str;
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

    /**
     * Demo/dev helper: backfills $days of random-but-realistic usage for a
     * customer via the same recordUsage() path the API uses, so the
     * dashboard and billing math have real data to show without needing an
     * external client to hit POST /usage repeatedly. Never runs in
     * production — see CustomerController::simulateUsage().
     *
     * @return array{days: int, total_units: int}
     */
    public function simulateUsage(Customer $customer, int $days = 7): array
    {
        $subscription = $customer->activeSubscription;

        if (! $subscription) {
            throw ValidationException::withMessages([
                'customer' => 'This customer has no active subscription.',
            ]);
        }

        $today = Carbon::today();
        $totalUnits = 0;
        $daysGenerated = 0;

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);

            if ($date->lt($subscription->current_period_start)) {
                continue;
            }

            $units = random_int(20, 200);

            $this->recordUsage($customer, (string) Str::uuid(), $units, $date->toDateString());

            $totalUnits += $units;
            $daysGenerated++;
        }

        return ['days' => $daysGenerated, 'total_units' => $totalUnits];
    }
}
