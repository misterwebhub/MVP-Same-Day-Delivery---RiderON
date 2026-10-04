<?php

namespace App\Http\Requests\Pricing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'route_id' => ['required', 'integer', 'exists:routes,id'],
            'route_schedule_id' => ['required', 'integer', 'exists:route_schedules,id'],
            'weight_slab' => ['required', 'string', Rule::in(array_keys(config('pricing.weight_slab_grams')))],
            'quantity' => ['required', 'integer', 'min:1'],
            'declared_value_paise' => ['required', 'integer', 'min:0'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'door_pickup' => ['nullable', 'boolean'],
        ];
    }
}
