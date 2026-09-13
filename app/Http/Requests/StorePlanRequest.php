<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'base_price_cents' => ['required', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'billing_cycle' => ['required', 'in:monthly,quarterly,yearly'],
            'included_units' => ['required', 'integer', 'min:0'],
            'overage_rate_cents' => ['required', 'integer', 'min:0'],
        ];
    }
}
