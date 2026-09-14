<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UsageService;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeatherController extends Controller
{
    public function __construct(
        private WeatherService $weather,
        private UsageService $usage,
    ) {}

    public function current(Request $request): JsonResponse
    {
        $customer = $request->attributes->get('apiCustomer');

        // WeatherCloud's own business API — see GeocodeController for why
        // this is gated to one merchant rather than shared platform-wide.
        if ($customer->merchant->slug !== 'weathercloud') {
            return response()->json(['error' => 'This endpoint is not available for your account.'], 403);
        }

        if (! $customer->activeSubscription) {
            return response()->json(['error' => 'No active subscription.'], 403);
        }

        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $result = $this->weather->current((float) $validated['lat'], (float) $validated['lon']);

        if ($result === null) {
            return response()->json(['error' => 'Weather service unavailable.'], 503);
        }

        $this->usage->recordUsage(
            $customer,
            eventKey: "{$customer->id}_weather_".now()->valueOf(),
            units: 1,
            recordedDate: now()->toDateString(),
            metadata: ['endpoint' => '/weather', 'lat' => $validated['lat'], 'lon' => $validated['lon']],
        );

        return response()->json($result + ['timestamp' => now()->toIso8601String()]);
    }
}
