<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
            // Only honoured by the mock gateway (PAYMENT_DRIVER=mock), to
            // deterministically exercise the signature-failure path in dev/tests.
            'force_failure' => ['sometimes', 'boolean'],
        ];
    }
}
