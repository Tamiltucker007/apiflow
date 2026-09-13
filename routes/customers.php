<?php

use App\Enums\UserRole;
use App\Http\Controllers\ApiCredentialController;
use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

// Customer registration + API key management. Included from web.php inside
// the merchants/{merchant} group, so prefix/name/middleware are inherited.
// merchant_staff can view; registering customers or managing keys needs admin rights.
// Static "create" segment must be registered before the {customer} wildcard below.
Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');

Route::middleware('role:'.UserRole::SuperAdmin->value.','.UserRole::MerchantAdmin->value)->group(function () {
    Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');

    Route::post('customers/{customer}/api-keys', [ApiCredentialController::class, 'store'])->name('customers.api-keys.store');
    Route::delete('customers/{customer}/api-keys/{credential}', [ApiCredentialController::class, 'destroy'])->name('customers.api-keys.destroy');
});

Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
