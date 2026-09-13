<?php

namespace App\Services;

use App\Models\ApiCredential;
use App\Models\Customer;
use Illuminate\Support\Str;

class ApiCredentialService
{
    private const KEY_PREFIX = 'af_live_';

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
    }

    /**
     * Resolves a plaintext key to its credential, checking active/expiry,
     * and records usage. Returns null for any invalid key so callers can't
     * distinguish "wrong key" from "revoked key" (no user enumeration).
     */
    public function resolveFromKey(string $key): ?ApiCredential
    {
        $credential = ApiCredential::with(['customer'])
            ->where('key_hash', hash('sha256', $key))
            ->where('is_active', true)
            ->first();

        if (! $credential) {
            return null;
        }

        if ($credential->expires_at && $credential->expires_at->isPast()) {
            return null;
        }

        $credential->update(['last_used_at' => now()]);

        return $credential;
    }
}
