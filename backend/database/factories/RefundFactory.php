<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Refund>
 */
class RefundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_id' => null,
            'requested_by' => Refund::REQUESTED_BY_CUSTOMER,
            'reason' => 'Factory-generated refund',
            'amount_paise' => 5000,
            'status' => Refund::STATUS_PENDING,
            'provider_refund_id' => null,
            'approved_by' => null,
        ];
    }
}
