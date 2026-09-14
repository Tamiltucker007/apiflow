<?php

use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\InvoiceController;
use App\Http\Controllers\Portal\LoginController;
use Illuminate\Support\Facades\Route;

// Self-service customer portal — a separate identity space from the
// merchant-admin routes in web.php (guard "customer", not "web"). No
// merchants/{merchant} prefix: a logged-in customer's own merchant_id is
// implicit from their session, never a route param a caller could tamper
// with. Included from web.php.
Route::prefix('portal')->name('portal.')->group(function () {
    Route::middleware('guest.customer')->group(function () {
        Route::get('/login', [LoginController::class, 'create'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth.customer')->group(function () {
        Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    });
});
