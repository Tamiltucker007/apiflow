<?php

namespace App\Services;

use App\Models\Merchant;

// Holds the current request's merchant; set once by middleware, read by MerchantScope.
class MerchantContext
{
    protected ?Merchant $merchant = null;

    protected bool $bypassScoping = false;

    public function set(Merchant $merchant): void
    {
        $this->merchant = $merchant;
        $this->bypassScoping = false;
    }

    public function merchant(): ?Merchant
    {
        return $this->merchant;
    }

    public function merchantId(): ?int
    {
        return $this->merchant?->id;
    }

    // Used for super_admin requests so they see every tenant's data.
    public function bypassScoping(): void
    {
        $this->bypassScoping = true;
        $this->merchant = null;
    }

    public function bypassesScoping(): bool
    {
        return $this->bypassScoping;
    }
}
