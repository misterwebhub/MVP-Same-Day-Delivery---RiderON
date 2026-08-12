<?php

namespace App\Http\Requests\Orders;

use App\Models\Parcel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quote_token' => ['required', 'string'],
            'booking_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'sender_name' => ['required', 'string', 'max:150'],
            'sender_phone' => ['required', 'string', 'max:15'],
            'sender_landmark' => ['nullable', 'string', 'max:255'],
            'receiver_name' => ['required', 'string', 'max:150'],
            'receiver_phone' => ['required', 'string', 'max:15'],
            'receiver_landmark' => ['nullable', 'string', 'max:255'],
            'parcel_type' => ['required', Rule::in([
                Parcel::TYPE_DOCUMENTS,
                Parcel::TYPE_CLOTHING,
                Parcel::TYPE_ELECTRONICS,
                Parcel::TYPE_GIFTS,
                Parcel::TYPE_BOOKS,
                Parcel::TYPE_OTHER,
            ])],
            'special_instructions' => ['nullable', 'string', 'max:1000'],
            'prohibited_items_accepted' => ['required', 'accepted'],
        ];
    }
}
