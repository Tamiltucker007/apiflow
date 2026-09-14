<?php

use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\AuthenticateCustomer;
use App\Http\Middleware\EnsureMerchantAccess;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\RedirectIfCustomerAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => EnsureRole::class,
            'merchant.access' => EnsureMerchantAccess::class,
            'auth.apikey' => AuthenticateApiKey::class,
            'auth.customer' => AuthenticateCustomer::class,
            'guest.customer' => RedirectIfCustomerAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // /api/* is a pure JSON API — content-negotiation headers shouldn't
        // decide whether errors render as JSON or an HTML redirect.
        $exceptions->shouldRenderJsonWhen(fn (Request $request, \Throwable $e) => $request->is('api/*') || $request->expectsJson());
    })->create();
