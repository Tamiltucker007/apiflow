<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// Mirrors AuthenticateCustomer: Laravel's built-in "auth" always redirects
// to route('login'), which now belongs to the customer side, not admin.
class AuthenticateAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('web')->check()) {
            // Only GET URLs are safe to "resume" after login — remembering a
            // POST/PUT/DELETE action (e.g. /admin/logout) would redirect the
            // next login there via GET and 405.
            return $request->isMethod('get')
                ? redirect()->guest(route('admin.login'))
                : redirect()->to(route('admin.login'));
        }

        Auth::shouldUse('web');

        return $next($request);
    }
}
