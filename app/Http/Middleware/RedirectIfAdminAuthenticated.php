<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// Mirrors RedirectIfCustomerAuthenticated: sends an already-logged-in admin
// straight to their dashboard instead of showing the login form again.
class RedirectIfAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if ($user) {
            return redirect()->route('admin.dashboard', $user->merchant_id);
        }

        return $next($request);
    }
}
