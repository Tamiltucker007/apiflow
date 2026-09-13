<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExchangeRateService;
use App\Services\UsageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    public function __construct(
        private ExchangeRateService $rates,
        private UsageService $usage,
    ) {}

    public function convert(Request $request): JsonResponse
    {
        $customer = $request->attributes->get('apiCustomer');

        if (! $customer->activeSubscription) {
            return response()->json(['error' => 'No active subscription.'], 403);
        }

        $validated = $request->validate([
            'from' => ['required', 'string', 'size:3'],
            'to' => ['required', 'string', 'size:3'],
        ]);

        $rate = $this->rates->convert($validated['from'], $validated['to']);

        if ($rate === null) {
            return response()->json(['error' => 'Exchange rate service unavailable.'], 503);
        }

        // 1 API request = 1 usage unit. A unique per-millisecond key keeps
        // repeat calls from colliding; a genuinely retried request (same
        // key) is handled idempotently by UsageService regardless.
        $this->usage->recordUsage(
            $customer,
            eventKey: "{$customer->id}_exchange-rate_".now()->valueOf(),
            units: 1,
            recordedDate: now()->toDateString(),
            metadata: ['endpoint' => '/exchange-rate', 'from' => $validated['from'], 'to' => $validated['to']],
        );

        return response()->json([
            'from' => $validated['from'],
            'to' => $validated['to'],
            'rate' => $rate,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
