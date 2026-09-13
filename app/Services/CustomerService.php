<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Merchant;

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
}
