<?php

use App\Enums\UserRole;
use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

// Customer registration routes. Included from web.php inside the
// merchants/{merchant} group, so prefix/name/middleware are inherited.
// merchant_staff can view; registering a new customer needs admin rights.
Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');

Route::middleware('role:'.UserRole::SuperAdmin->value.','.UserRole::MerchantAdmin->value)->group(function () {
    Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
});
