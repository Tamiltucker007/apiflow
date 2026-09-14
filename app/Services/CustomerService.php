<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Merchant;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    public function create(Merchant $merchant, array $data): Customer
    {
        return Customer::create([
            'merchant_id' => $merchant->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        return $customer;
    }

    /**
     * Soft-deletes a customer. Their subscriptions, invoices, and API
     * credentials are untouched — a customer_id can still resolve them,
     * it's just hidden from the default customer list. Refuses while an
     * active subscription exists so billing keeps a live customer to bill.
     */
    public function delete(Customer $customer): void
    {
        if ($customer->activeSubscription()->exists()) {
            throw ValidationException::withMessages([
                'customer' => 'This customer has an active subscription and cannot be deleted. Cancel it first.',
            ]);
        }

        $customer->delete();
    }

    /**
     * Issues (or replaces) this customer's self-service portal password.
     * Same pattern as ApiCredentialService::generateKey() — only the hash
     * is ever stored, the plaintext is returned once for the caller to
     * flash to the admin, who is expected to relay it to the customer
     * out of band (no mail transport is assumed to be configured here).
     */
    public function generatePortalPassword(Customer $customer): string
    {
        $password = Str::password(12, symbols: false);

        $customer->update(['password' => $password]);

        return $password;
    }
}
