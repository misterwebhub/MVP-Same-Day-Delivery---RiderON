<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return AdminAccess::canManageMasterData();
    }

    public function rules(): array
    {
        $customerId = $this->route('customer')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($customerId)],
            'status' => ['required', Rule::in([
                User::STATUS_ACTIVE,
                User::STATUS_SUSPENDED,
                User::STATUS_DELETED,
            ])],
        ];
    }
}
