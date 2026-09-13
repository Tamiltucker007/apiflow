<?php

namespace App\Http\Middleware;

use App\Models\Merchant;
use App\Services\MerchantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Checks the user can access the {merchant} route param and sets MerchantContext.
class EnsureMerchantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $merchantParam = $request->route('merchant');

        $merchant = $merchantParam instanceof Merchant
            ? $merchantParam
            : Merchant::findOrFail($merchantParam);

        if ($user->isSuperAdmin()) {
            app(MerchantContext::class)->bypassScoping();
        } elseif ($user->merchant_id !== $merchant->id) {
            abort(403, 'You do not have access to this merchant.');
        } else {
            app(MerchantContext::class)->set($merchant);
        }

        $request->route()->setParameter('merchant', $merchant);

        return $next($request);
    }
}
