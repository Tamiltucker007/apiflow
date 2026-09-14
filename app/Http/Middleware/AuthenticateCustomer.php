<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// Mirrors AuthenticateAdmin, but this one owns the bare route('login').
class AuthenticateCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('customer')->check()) {
            return redirect()->guest(route('login'));
        }

        Auth::shouldUse('customer');

        return $next($request);
    }
}
