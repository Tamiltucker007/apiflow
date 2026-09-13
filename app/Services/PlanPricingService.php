<?php

namespace App\Services;

use App\Models\Plan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PlanPricingService
{
    private const TTL_SECONDS = 600;

    // TTL as a backstop; PlanObserver clears this immediately on update/delete,
    // so staleness in practice is bounded by the observer, not the TTL.
    public function getCachedPlan(int $planId): Plan
    {
        $key = "plan:{$planId}";

        Log::debug(Cache::has($key) ? "Plan cache HIT for {$key}" : "Plan cache MISS for {$key}");

        return Cache::remember($key, self::TTL_SECONDS, fn () => Plan::findOrFail($planId));
    }
}
