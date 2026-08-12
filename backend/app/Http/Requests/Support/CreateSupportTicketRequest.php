<?php

namespace App\Http\Requests\Support;

use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'category' => ['required', Rule::in([
                SupportTicket::CATEGORY_PAYMENT,
                SupportTicket::CATEGORY_PICKUP,
                SupportTicket::CATEGORY_DELIVERY,
                SupportTicket::CATEGORY_RIDER,
                SupportTicket::CATEGORY_WRONG_PARCEL,
                SupportTicket::CATEGORY_DAMAGED_PARCEL,
                SupportTicket::CATEGORY_RECEIVER_UNAVAILABLE,
                SupportTicket::CATEGORY_CANCELLATION,
                SupportTicket::CATEGORY_REFUND,
                SupportTicket::CATEGORY_OTHER,
            ])],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }
}
