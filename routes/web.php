<?php

use App\Enums\UserRole;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Grouped by access level: guest auth, per-tenant (/merchants/{merchant}).
// Deliberately unnamed: Laravel's RedirectIfAuthenticated middleware treats a
// route named 'home' as its default post-login redirect target, which would
// send an authenticated user straight back into this same redirect -> /login
// -> back here -> infinite loop. AppServiceProvider overrides that target
// explicitly instead (see RedirectIfAuthenticated::redirectUsing()).
Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::prefix('merchants/{merchant}')->name('merchants.')->middleware('merchant.access')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'merchant'])->name('dashboard');
        Route::get('plans', [PlanController::class, 'index'])->name('plans.index');
        Route::get('plans/data', [PlanController::class, 'data'])->name('plans.data');
        Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('subscriptions/data', [SubscriptionController::class, 'data'])->name('subscriptions.data');
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/data', [InvoiceController::class, 'data'])->name('invoices.data');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');

        require __DIR__.'/customers.php';

        // merchant_staff is read-only; writes need merchant_admin.
        Route::middleware('role:'.UserRole::MerchantAdmin->value)->group(function () {
            Route::get('plans/create', [PlanController::class, 'create'])->name('plans.create');
            Route::post('plans', [PlanController::class, 'store'])->name('plans.store');
            Route::get('plans/{plan}/edit', [PlanController::class, 'edit'])->name('plans.edit');
            Route::put('plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
            Route::put('plans/{plan}/toggle', [PlanController::class, 'toggle'])->name('plans.toggle');
            Route::delete('plans/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy');

            Route::post('subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
            Route::put('subscriptions/{subscription}/change-plan', [SubscriptionController::class, 'changePlan'])->name('subscriptions.change-plan');
            Route::post('subscriptions/{subscription}/generate-invoice', [SubscriptionController::class, 'generateInvoice'])->name('subscriptions.generate-invoice');
            Route::delete('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

            // Team management — admin-only end to end (unlike Plans/Customers/
            // Subscriptions, staff don't get read access here either: this is
            // who can log in and with what access, not business data).
            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::get('users/data', [UserController::class, 'data'])->name('users.data');
            Route::get('users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('users', [UserController::class, 'store'])->name('users.store');
            Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::put('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        });
    });
});
