<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $merchant = $this->route('merchant');

        return [
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')->where('merchant_id', $merchant->id),
            ],
            'plan_id' => [
                'required',
                Rule::exists('plans', 'id')->where('merchant_id', $merchant->id)->where('is_active', true),
            ],
        ];
    }
}
