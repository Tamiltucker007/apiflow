<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Merchant;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    public function create(Merchant $merchant, array $data): Customer
    {
        return Customer::create([
            'merchant_id' => $merchant->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'external_id' => $data['external_id'] ?? null,
        ]);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'external_id' => $data['external_id'] ?? null,
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
}
