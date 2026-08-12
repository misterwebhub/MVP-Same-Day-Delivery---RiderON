<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'provider_payment_id' => 'mock_pay_'.Str::random(14),
            'provider_signature' => null,
            'event_type' => PaymentTransaction::EVENT_CHECKOUT_VERIFY,
            'raw_payload' => ['note' => 'factory-generated'],
            'signature_verified' => true,
            'processed' => true,
            'created_at' => now(),
        ];
    }
}
