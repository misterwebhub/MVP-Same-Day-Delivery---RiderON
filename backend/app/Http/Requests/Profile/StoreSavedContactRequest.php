<?php

namespace App\Http\Requests\Profile;

use App\Models\SavedContact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSavedContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([SavedContact::TYPE_SENDER, SavedContact::TYPE_RECEIVER])],
            'label' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:15'],
            'station_id' => ['nullable', 'integer', 'exists:stations,id'],
            'landmark' => ['nullable', 'string', 'max:255'],
        ];
    }
}
