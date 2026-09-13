<?php

use App\Enums\UserRole;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

// Grouped by access level: guest auth, super-admin (/admin), per-tenant (/merchants/{merchant}).
Route::redirect('/', '/login')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::prefix('admin')->name('admin.')->middleware('role:' . UserRole::SuperAdmin->value)->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
    });

    Route::prefix('merchants/{merchant}')->name('merchants.')->middleware('merchant.access')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'merchant'])->name('dashboard');
        Route::get('plans', [PlanController::class, 'index'])->name('plans.index');
        Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');

        require __DIR__.'/customers.php';

        // merchant_staff is read-only; writes need merchant_admin or super_admin.
        Route::middleware('role:'.UserRole::SuperAdmin->value.','.UserRole::MerchantAdmin->value)->group(function () {
            Route::get('plans/create', [PlanController::class, 'create'])->name('plans.create');
            Route::post('plans', [PlanController::class, 'store'])->name('plans.store');
            Route::put('plans/{plan}/toggle', [PlanController::class, 'toggle'])->name('plans.toggle');

            Route::post('subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
        });
    });
});
