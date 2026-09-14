<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Merchant admin area — "/admin" prefix + "admin." names, guard "web".
// Uses 'auth.admin'/'guest.admin' since Laravel's built-in 'auth'/'guest'
// always redirect to route('login'), which now belongs to the customer side.
Route::prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/login');

    Route::middleware('guest.admin')->group(function () {
        Route::get('/login', [LoginController::class, 'create'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth.admin')->group(function () {
        Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

        Route::prefix('merchants/{merchant}')->middleware('merchant.access')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'merchant'])->name('dashboard');

            Route::get('plans/data', [PlanController::class, 'data'])->name('plans.data');
            Route::put('plans/{plan}/toggle', [PlanController::class, 'toggle'])->name('plans.toggle');
            Route::resource('plans', PlanController::class)->except('show');

            Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
            Route::get('subscriptions/data', [SubscriptionController::class, 'data'])->name('subscriptions.data');
            Route::post('subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
            Route::put('subscriptions/{subscription}/change-plan', [SubscriptionController::class, 'changePlan'])->name('subscriptions.change-plan');
            Route::post('subscriptions/{subscription}/generate-invoice', [SubscriptionController::class, 'generateInvoice'])->name('subscriptions.generate-invoice');
            Route::delete('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

            Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
            Route::get('invoices/data', [InvoiceController::class, 'data'])->name('invoices.data');
            Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
            Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');

            require __DIR__.'/admin-customers.php';

            // Team management — who can log in, not business data.
            Route::get('users/data', [UserController::class, 'data'])->name('users.data');
            Route::put('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
            Route::resource('users', UserController::class)->except(['show', 'destroy']);
        });
    });
});
