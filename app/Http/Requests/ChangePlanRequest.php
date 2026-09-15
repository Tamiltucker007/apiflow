<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $merchant = $this->route('merchant');
        $subscription = $this->route('subscription');

        return [
            'plan_id' => [
                'required',
                Rule::exists('plans', 'id')
                    ->where('merchant_id', $merchant->id)
                    ->where('is_active', true)
                    ->where('billing_cycle', $subscription->plan->billing_cycle->value),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.exists' => 'You can only switch to a plan with the same billing interval right now — a full cycle-length change (e.g. monthly to quarterly) takes effect at the next renewal.',
        ];
    }
}
