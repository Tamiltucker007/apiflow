<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    private const BASE_URL = 'https://api.open-meteo.com/v1/forecast';

    /**
     * Free, keyless demo lookup (Open-Meteo). Returns null on any failure so
     * the controller can respond 503 rather than surface a raw HTTP exception.
     */
    public function current(float $lat, float $lon): ?array
    {
        try {
            $response = Http::timeout(5)->get(self::BASE_URL, [
                'latitude' => $lat,
                'longitude' => $lon,
                'current_weather' => 'true',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Weather lookup failed', ['lat' => $lat, 'lon' => $lon, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful() || ! $response->json('current_weather')) {
            return null;
        }

        return $response->json('current_weather');
    }
}
