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
            // Only GET URLs are safe to "resume" after login — see
            // AuthenticateAdmin for why POST/PUT/DELETE must not be stored.
            return $request->isMethod('get')
                ? redirect()->guest(route('login'))
                : redirect()->to(route('login'));
        }

        Auth::shouldUse('customer');

        return $next($request);
    }
}
