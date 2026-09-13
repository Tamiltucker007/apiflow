<?php

namespace App\Services;

use App\Models\Merchant;

// Holds the current request's merchant; set once by middleware, read by MerchantScope.
class MerchantContext
{
    protected ?Merchant $merchant = null;

    public function set(Merchant $merchant): void
    {
        $this->merchant = $merchant;
    }

    public function merchant(): ?Merchant
    {
        return $this->merchant;
    }

    public function merchantId(): ?int
    {
        return $this->merchant?->id;
    }
}
