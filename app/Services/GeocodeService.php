<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodeService
{
    private const BASE_URL = 'https://nominatim.openstreetmap.org/search';

    /**
     * Free, keyless demo lookup (OpenStreetMap Nominatim — a User-Agent is
     * required by their usage policy). Returns null on any failure so the
     * controller can respond 503 rather than surface a raw HTTP exception.
     */
    public function lookup(string $address): ?array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders(['User-Agent' => 'APIFlow-Demo/1.0'])
                ->get(self::BASE_URL, ['q' => $address, 'format' => 'json', 'limit' => 1]);
        } catch (\Throwable $e) {
            Log::warning('Geocode lookup failed', ['address' => $address, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful() || empty($response->json())) {
            return null;
        }

        $result = $response->json()[0];

        return [
            'lat' => (float) $result['lat'],
            'lon' => (float) $result['lon'],
            'display_name' => $result['display_name'],
        ];
    }
}
