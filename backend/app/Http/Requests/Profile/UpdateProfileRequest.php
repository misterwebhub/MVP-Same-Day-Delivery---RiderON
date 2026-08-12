<?php

namespace App\Http\Requests\Profile;

use App\Models\CustomerProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'preferred_language' => ['nullable', Rule::in([
                CustomerProfile::LANGUAGE_ENGLISH,
                CustomerProfile::LANGUAGE_HINDI,
            ])],
        ];
    }
}
