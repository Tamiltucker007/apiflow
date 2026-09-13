<?php

namespace App\Services;

use App\Models\ApiCredential;
use App\Models\Customer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ApiCredentialService
{
    private const KEY_PREFIX = 'af_live_';

    private const CACHE_TTL_SECONDS = 300;

    /**
     * Generates a key and returns it plaintext once, alongside the stored
     * credential (which only ever holds the hash).
     *
     * @return array{key: string, credential: ApiCredential}
     */
    public function generateKey(Customer $customer, ?string $name = null): array
    {
        $key = self::KEY_PREFIX.Str::random(40);

        $credential = ApiCredential::create([
            'merchant_id' => $customer->merchant_id,
            'customer_id' => $customer->id,
            'key_prefix' => self::KEY_PREFIX,
            'key_hash' => hash('sha256', $key),
            'name' => $name,
            'is_active' => true,
        ]);

        return ['key' => $key, 'credential' => $credential];
    }

    public function revokeKey(ApiCredential $credential): void
    {
        $credential->update(['is_active' => false]);
        Cache::forget("api_cred:{$credential->key_hash}");
    }

    /**
     * Resolves a plaintext key to its credential, checking active/expiry,
     * and records usage. Returns null for any invalid key so callers can't
     * distinguish "wrong key" from "revoked key" (no user enumeration).
     * The merchant+customer lookup is cached briefly since every API call
     * hits this; revokeKey() clears it immediately rather than waiting on
     * the TTL.
     */
    public function resolveFromKey(string $key): ?ApiCredential
    {
        $hash = hash('sha256', $key);

        $credential = Cache::remember(
            "api_cred:{$hash}",
            self::CACHE_TTL_SECONDS,
            fn () => ApiCredential::with(['customer', 'merchant'])->where('key_hash', $hash)->first()
        );

        if (! $credential || ! $credential->is_active) {
            Cache::forget("api_cred:{$hash}");

            return null;
        }

        if ($credential->expires_at && $credential->expires_at->isPast()) {
            return null;
        }

        $credential->update(['last_used_at' => now()]);

        return $credential;
    }
}
