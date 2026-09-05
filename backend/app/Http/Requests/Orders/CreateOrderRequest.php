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
            // Manual pickup/delivery address — only honoured server-side when
            // the relevant end of the resolved route (origin for pickup,
            // destination for delivery) is in a city opted into manual
            // address entry (currently Kanpur; see
            // OrderController::resolveManualAddress()). Submitting these for
            // any other city is silently ignored rather than rejected, same
            // leniency as the landmark fields.
            'pickup_address_text' => ['nullable', 'string', 'max:500'],
            'pickup_latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:pickup_longitude'],
            'pickup_longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:pickup_latitude'],
            'delivery_address_text' => ['nullable', 'string', 'max:500'],
            'delivery_latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:delivery_longitude'],
            'delivery_longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:delivery_latitude'],
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
