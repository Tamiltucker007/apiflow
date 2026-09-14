<?php

use App\Http\Controllers\ApiCredentialController;
use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

// Included from admin.php, so names come out as admin.customers.*.
// Static "create" segment must be registered before the {customer} wildcard.
Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
Route::get('customers/data', [CustomerController::class, 'data'])->name('customers.data');
Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

Route::post('customers/{customer}/api-keys', [ApiCredentialController::class, 'store'])->name('customers.api-keys.store');
Route::delete('customers/{customer}/api-keys/{credential}', [ApiCredentialController::class, 'destroy'])->name('customers.api-keys.destroy');

Route::post('customers/{customer}/portal-password', [CustomerController::class, 'generatePortalPassword'])->name('customers.portal-password.store');

// Demo/dev only — see CustomerController::simulateUsage().
Route::post('customers/{customer}/simulate-usage', [CustomerController::class, 'simulateUsage'])->name('customers.simulate-usage');

Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
