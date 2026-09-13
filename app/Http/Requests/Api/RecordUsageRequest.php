<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RecordUsageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_key' => ['required', 'string', 'max:255'],
            'units' => ['required', 'integer', 'min:1'],
            'recorded_date' => ['required', 'date', 'before_or_equal:today'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
