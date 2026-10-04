<?php

namespace App\Http\Requests\Admin;

use App\Models\PricingRule;
use App\Support\AdminAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PricingRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return AdminAccess::canManagePricing();
    }

    public function rules(): array
    {
        return [
            'route_id' => ['nullable', 'integer', 'exists:routes,id'],
            'rule_type' => ['required', Rule::in([
                PricingRule::TYPE_BASE,
                PricingRule::TYPE_WEIGHT_SLAB,
                PricingRule::TYPE_PEAK_HOUR,
                PricingRule::TYPE_PLATFORM_FEE,
                PricingRule::TYPE_TAX,
            ])],
            'min_weight_grams' => ['nullable', 'integer', 'min:0'],
            'max_weight_grams' => ['nullable', 'integer', 'min:0', 'gte:min_weight_grams'],
            'amount_paise' => ['nullable', 'integer', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after:effective_from'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'route_id' => $this->input('route_id') !== '' ? $this->input('route_id') : null,
        ]);
    }
}
