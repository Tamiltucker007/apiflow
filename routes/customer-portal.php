<?php

use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\InvoiceController;
use App\Http\Controllers\Portal\LoginController;
use App\Http\Controllers\Portal\PlanSelectionController;
use App\Http\Controllers\Portal\ProfileController;
use App\Http\Controllers\Portal\RegistrationController;
use App\Http\Controllers\Portal\RegistrationLandingController;
use App\Http\Controllers\Portal\SubscriptionController;
use Illuminate\Support\Facades\Route;

// Customer-facing routes — unprefixed (no "/portal"), guard "customer".
// Controllers/views still live under Portal/portal/ — internal naming only.
Route::middleware('guest.customer')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/register', [RegistrationLandingController::class, 'index'])->name('register');
    Route::get('/register/{merchant:slug}', [RegistrationController::class, 'create'])->name('register.show');
    Route::post('/register/{merchant:slug}', [RegistrationController::class, 'store'])->name('register.store');
});

Route::middleware('auth.customer')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/dashboard/simulate-usage', [DashboardController::class, 'simulateUsage'])->name('dashboard.simulate-usage');
    Route::get('/plans/choose', [PlanSelectionController::class, 'create'])->name('plans.choose');
    Route::post('/plans/choose', [PlanSelectionController::class, 'store'])->name('plans.choose.store');
    Route::get('/subscription', [SubscriptionController::class, 'show'])->name('subscription');
    Route::put('/subscription/change-plan', [SubscriptionController::class, 'changePlan'])->name('subscription.change-plan');
    Route::get('/subscription/history', [SubscriptionController::class, 'history'])->name('subscription.history');
    Route::get('/usage', [SubscriptionController::class, 'usage'])->name('usage');
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    Route::post('/invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
});
