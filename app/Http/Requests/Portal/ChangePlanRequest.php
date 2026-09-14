<?php

namespace App\Http\Requests\Portal;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ChangePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        return [
            'plan_id' => [
                'required',
                Rule::exists('plans', 'id')->where('merchant_id', $customer->merchant_id)->where('is_active', true),
            ],
        ];
    }
}
