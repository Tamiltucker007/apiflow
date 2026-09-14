<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateApiKeyRequest;
use App\Models\ApiCredential;
use App\Models\Customer;
use App\Models\Merchant;
use App\Services\ApiCredentialService;
use App\Traits\VerifiesTenantOwnership;
use Illuminate\Http\RedirectResponse;

class ApiCredentialController extends Controller
{
    use VerifiesTenantOwnership;

    public function __construct(private ApiCredentialService $credentials) {}

    public function store(GenerateApiKeyRequest $request, Merchant $merchant, Customer $customer): RedirectResponse
    {
        $this->ensureBelongsToMerchant($customer, $merchant);

        $result = $this->credentials->generateKey($customer, $request->validated('name'));

        // The plaintext key is only ever available right now — flash it once.
        return redirect()->route('admin.customers.show', [$merchant, $customer])
            ->with('newApiKey', $result['key']);
    }

    public function destroy(Merchant $merchant, Customer $customer, ApiCredential $credential): RedirectResponse
    {
        $this->ensureBelongsToMerchant($customer, $merchant);
        $this->ensureBelongsToMerchant($credential, $merchant);

        $this->credentials->revokeKey($credential);

        return redirect()->route('admin.customers.show', [$merchant, $customer])
            ->with('status', 'API key revoked.');
    }
}
