<?php

use App\Http\Controllers\ApiCredentialController;
use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

// Included from admin.php, so names come out as admin.customers.*.
Route::get('customers/data', [CustomerController::class, 'data'])->name('customers.data');

Route::resource('customers', CustomerController::class);
Route::put('customers/{customer}/toggle', [CustomerController::class, 'toggle'])->name('customers.toggle');

Route::post('customers/{customer}/api-keys', [ApiCredentialController::class, 'store'])->name('customers.api-keys.store');
Route::delete('customers/{customer}/api-keys/{credential}', [ApiCredentialController::class, 'destroy'])->name('customers.api-keys.destroy');

Route::post('customers/{customer}/portal-password', [CustomerController::class, 'generatePortalPassword'])->name('customers.portal-password.store');

// Demo/dev only — see CustomerController::simulateUsage().
Route::post('customers/{customer}/simulate-usage', [CustomerController::class, 'simulateUsage'])->name('customers.simulate-usage');
