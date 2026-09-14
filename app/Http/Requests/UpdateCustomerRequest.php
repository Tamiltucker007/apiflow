<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $merchant = $this->route('merchant');
        $customer = $this->route('customer');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('customers', 'email')->where('merchant_id', $merchant->id)->ignore($customer),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
