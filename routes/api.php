<?php

use App\Http\Controllers\Api\ExchangeRateController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\UsageController;
use Illuminate\Support\Facades\Route;

// Stateless, API-key authenticated. auth.apikey resolves the credential
// before throttle:api-credential runs, so the rate limit keys off it.
Route::middleware(['auth.apikey', 'throttle:api-credential'])->prefix('v1')->group(function () {
    Route::get('exchange-rate', [ExchangeRateController::class, 'convert']);
    Route::post('usage', [UsageController::class, 'store']);
    Route::get('invoices', [InvoiceController::class, 'index']);
    Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download']);
});
