<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GeocodeService;
use App\Services\UsageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeocodeController extends Controller
{
    public function __construct(
        private GeocodeService $geocode,
        private UsageService $usage,
    ) {}

    public function lookup(Request $request): JsonResponse
    {
        $customer = $request->attributes->get('apiCustomer');

        // This is GeoLocate Pro's own business API — like /exchange-rate
        // belongs to FinPay, it only makes sense for that merchant's own
        // customers, not a platform-wide endpoint every API key can hit.
        if ($customer->merchant->slug !== 'geolocate') {
            return response()->json(['error' => 'This endpoint is not available for your account.'], 403);
        }

        if (! $customer->activeSubscription) {
            return response()->json(['error' => 'No active subscription.'], 403);
        }

        $validated = $request->validate([
            'address' => ['required', 'string', 'max:255'],
        ]);

        $result = $this->geocode->lookup($validated['address']);

        if ($result === null) {
            return response()->json(['error' => 'Geocoding service unavailable.'], 503);
        }

        $this->usage->recordUsage(
            $customer,
            eventKey: "{$customer->id}_geocode_".now()->valueOf(),
            units: 1,
            recordedDate: now()->toDateString(),
            metadata: ['endpoint' => '/geocode', 'address' => $validated['address']],
        );

        return response()->json($result + ['timestamp' => now()->toIso8601String()]);
    }
}
