<?php

use App\Http\Controllers\Api\ExchangeRateController;
use App\Http\Controllers\Api\GeocodeController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\UsageController;
use App\Http\Controllers\Api\WeatherController;
use Illuminate\Support\Facades\Route;

// Stateless, API-key authenticated. auth.apikey resolves the credential
// before throttle:api-credential runs, so the rate limit keys off it.
//
// exchange-rate/geocode/weather are each one merchant's own flavor of
// business API (FinPay/GeoLocate/WeatherCloud) — every other route below
// is generic and works for any merchant's customer.
Route::middleware(['auth.apikey', 'throttle:api-credential'])->prefix('v1')->group(function () {
    Route::get('exchange-rate', [ExchangeRateController::class, 'convert']);
    Route::get('geocode', [GeocodeController::class, 'lookup']);
    Route::get('weather', [WeatherController::class, 'current']);
    Route::post('usage', [UsageController::class, 'store']);
    Route::get('invoices', [InvoiceController::class, 'index']);
    Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download']);
});
