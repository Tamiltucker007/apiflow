<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    private const BASE_URL = 'https://open.er-api.com/v6/latest/';

    /**
     * Free, keyless demo rate lookup. Returns null on any failure so the
     * controller can respond 503 rather than surface a raw HTTP exception.
     */
    public function convert(string $from, string $to): ?float
    {
        try {
            $response = Http::timeout(5)->get(self::BASE_URL.strtoupper($from));
        } catch (\Throwable $e) {
            Log::warning('Exchange rate lookup failed', ['from' => $from, 'to' => $to, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful() || $response->json('result') !== 'success') {
            return null;
        }

        return $response->json("rates.{$to}");
    }
}
