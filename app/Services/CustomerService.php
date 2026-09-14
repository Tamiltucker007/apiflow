<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Merchant;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    public function __construct(private SubscriptionService $subscriptions) {}

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
     * Soft-deletes a customer. An active subscription is cancelled first
     * (so nothing keeps billing a deleted customer); if that cancellation
     * fails, the delete is refused rather than leaving an orphaned state.
     * Invoices and API credentials are left untouched either way.
     */
    public function delete(Customer $customer): void
    {
        $active = $customer->activeSubscription;

        if ($active) {
            try {
                $this->subscriptions->cancel($active);
            } catch (ValidationException) {
                throw ValidationException::withMessages([
                    'customer' => "This customer's active subscription could not be cancelled, so the account cannot be deleted.",
                ]);
            }
        }

        $customer->delete();
    }

    /**
     * Toggles is_active. Blocks portal login and API access (see
     * AuthenticateApiKey and portal LoginController) without touching the
     * underlying subscription — reactivating restores access as-is.
     */
    public function toggleActive(Customer $customer): Customer
    {
        $customer->update(['is_active' => ! $customer->is_active]);

        return $customer;
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

    /**
     * Public self-registration (see routes/customer.php) — unlike create(),
     * this sets the customer's own chosen password directly instead of
     * requiring an admin to issue one afterward. The signup form only asks
     * for email/password, so name is derived from the email's local part;
     * the customer can rename themselves later if a profile-edit page is
     * ever added.
     */
    public function registerSelf(Merchant $merchant, string $email, string $password): Customer
    {
        return Customer::create([
            'merchant_id' => $merchant->id,
            'name' => Str::before($email, '@'),
            'email' => $email,
            'password' => $password,
        ]);
    }
}
