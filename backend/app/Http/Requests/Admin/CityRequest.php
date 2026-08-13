<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminAccess;
use Illuminate\Foundation\Http\FormRequest;

class CityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return AdminAccess::canManageMasterData();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
