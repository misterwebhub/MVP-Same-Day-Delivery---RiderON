<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => Payment::PROVIDER_MOCK,
            'provider_order_id' => 'mock_order_'.Str::random(14),
            'amount_paise' => 15000,
            'currency' => 'INR',
            'status' => Payment::STATUS_SUCCESS,
            'idempotency_key' => Str::uuid()->toString(),
        ];
    }
}
