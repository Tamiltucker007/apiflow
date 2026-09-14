<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// Laravel's built-in "auth" middleware always redirects to the "login" route
// regardless of which guard failed, which would send a logged-out portal
// visitor to the merchant admin login instead of /portal/login. This is the
// customer-guard equivalent, paired with RedirectIfCustomerAuthenticated.
class AuthenticateCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('customer')->check()) {
            return redirect()->guest(route('portal.login'));
        }

        Auth::shouldUse('customer');

        return $next($request);
    }
}
