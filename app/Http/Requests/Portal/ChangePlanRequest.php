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
        $currentPlan = $customer->activeSubscription?->plan;

        $rule = Rule::exists('plans', 'id')->where('merchant_id', $customer->merchant_id)->where('is_active', true);

        if ($currentPlan) {
            $rule->where('billing_cycle', $currentPlan->billing_cycle->value);
        }

        return ['plan_id' => ['required', $rule]];
    }

    public function messages(): array
    {
        return [
            'plan_id.exists' => 'You can only switch to a plan with the same billing interval right now — a full cycle-length change (e.g. monthly to quarterly) takes effect at the next renewal.',
        ];
    }
}
