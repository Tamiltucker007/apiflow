<?php

namespace App\Services;

use App\Models\Subscription;
use Carbon\Carbon;

class ProrationService
{
    // base_price_cents * segment_days / total_cycle_days, rounded to the
    // nearest cent. Same formula shape works for included_units too.
    public function prorate(int $amount, int $segmentDays, int $totalCycleDays): int
    {
        if ($totalCycleDays <= 0) {
            return $amount;
        }

        return (int) round($amount * $segmentDays / $totalCycleDays);
    }

    /**
     * Splits [periodStart, periodEnd] into per-plan segments at each
     * recorded plan change's effective_date. No changes -> one segment
     * covering the whole period. Explicit period bounds (not read from
     * $subscription->current_period_*) so this stays correct even after
     * the subscription has been advanced to its next cycle.
     *
     * @return array<int, array{plan_id: int, start: Carbon, end: Carbon}>
     */
    public function calculateSegments(Subscription $subscription, Carbon $periodStart, Carbon $periodEnd): array
    {
        $changes = $subscription->planChanges()
            ->whereBetween('effective_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->orderBy('effective_date')
            ->get();

        if ($changes->isEmpty()) {
            return [[
                'plan_id' => $subscription->plan_id,
                'start' => $periodStart->copy(),
                'end' => $periodEnd->copy(),
            ]];
        }

        $segments = [];

        $segments[] = [
            'plan_id' => $changes->first()->old_plan_id,
            'start' => $periodStart->copy(),
            'end' => $changes->first()->effective_date->copy()->subDay(),
        ];

        foreach ($changes as $i => $change) {
            $next = $changes->get($i + 1);

            $segments[] = [
                'plan_id' => $change->new_plan_id,
                'start' => $change->effective_date->copy(),
                'end' => $next ? $next->effective_date->copy()->subDay() : $periodEnd->copy(),
            ];
        }

        // A change effective on the period's first day produces a zero/negative
        // -day leading segment; drop it rather than bill a segment that never occurred.
        return array_values(array_filter($segments, fn ($s) => $s['start']->lte($s['end'])));
    }
}
