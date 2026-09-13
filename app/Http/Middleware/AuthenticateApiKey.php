<?php

namespace App\Http\Middleware;

use App\Services\ApiCredentialService;
use App\Services\MerchantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Resolves a customer's API key (Bearer or X-API-Key header) and primes
// MerchantContext, same role EnsureMerchantAccess plays for dashboard routes.
class AuthenticateApiKey
{
    public function __construct(private ApiCredentialService $credentials) {}

    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->extractKey($request);

        $credential = $key ? $this->credentials->resolveFromKey($key) : null;

        if (! $credential) {
            return response()->json(['error' => 'Invalid or revoked API key.'], 401);
        }

        app(MerchantContext::class)->set($credential->merchant);

        $request->attributes->set('apiCredential', $credential);
        $request->attributes->set('apiCustomer', $credential->customer);

        return $next($request);
    }

    private function extractKey(Request $request): ?string
    {
        return $request->bearerToken() ?? $request->header('X-API-Key');
    }
}
