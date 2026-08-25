<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminAccess;
use Illuminate\Foundation\Http\FormRequest;

class ResetPartnerPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return AdminAccess::canManageOrders();
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
