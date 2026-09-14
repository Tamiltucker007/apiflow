<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Mirrors the merchant-scoped uniqueness pattern already used by
// StoreCustomerRequest (admin-side) — email only has to be unique per
// merchant, not globally, since the same person could be a customer of
// two different merchants on this platform.
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $merchant = $this->route('merchant');

        return [
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('customers', 'email')->where('merchant_id', $merchant->id),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
