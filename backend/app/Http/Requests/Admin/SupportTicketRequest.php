<?php

namespace App\Http\Requests\Admin;

use App\Models\SupportTicket;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return AdminAccess::canManageOrders();
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                SupportTicket::STATUS_OPEN,
                SupportTicket::STATUS_IN_PROGRESS,
                SupportTicket::STATUS_RESOLVED,
                SupportTicket::STATUS_CLOSED,
            ])],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where(
                fn ($query) => $query->whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPS, User::ROLE_SUPPORT])
            )],
            'resolved_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'assigned_to' => $this->input('assigned_to') !== '' ? $this->input('assigned_to') : null,
            'resolved_at' => $this->input('resolved_at') !== '' ? $this->input('resolved_at') : null,
        ]);
    }
}
