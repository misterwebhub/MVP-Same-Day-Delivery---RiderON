<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Refund;
use App\Models\User;
use App\Services\PaymentGateway\PaymentGateway;
use App\Services\PaymentGateway\PaymentGatewayRefund;
use App\Services\Refunds\RefundProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class RefundProcessorTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use RefreshDatabase;

    private function processorWithGateway(PaymentGateway $gateway): RefundProcessor
    {
        return new RefundProcessor($gateway);
    }

    private function orderWithVerifiedPayment(): Order
    {
        $order = Order::factory()->create();
        $payment = Payment::factory()->for($order)->create(['status' => Payment::STATUS_SUCCESS]);
        PaymentTransaction::factory()->for($payment)->create([
            'provider_payment_id' => 'pay_verified_123',
            'signature_verified' => true,
        ]);

        return $order->fresh();
    }

    public function test_creates_and_processes_a_refund_via_the_gateway(): void
    {
        $order = $this->orderWithVerifiedPayment();
        $approver = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $gateway = \Mockery::mock(PaymentGateway::class);
        $gateway->shouldReceive('refund')
            ->once()
            ->with('pay_verified_123', 5000)
            ->andReturn(new PaymentGatewayRefund('rfnd_abc', 5000, 'processed'));

        $refund = $this->processorWithGateway($gateway)->createAndProcess($order, 5000, 'Customer requested', $approver->id);

        $this->assertSame(Refund::STATUS_COMPLETED, $refund->status);
        $this->assertSame('rfnd_abc', $refund->provider_refund_id);
        $this->assertSame($approver->id, $refund->approved_by);
        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'status' => Refund::STATUS_COMPLETED,
        ]);
    }

    public function test_maps_a_non_terminal_gateway_status_to_processing(): void
    {
        $order = $this->orderWithVerifiedPayment();
        $approver = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $gateway = \Mockery::mock(PaymentGateway::class);
        $gateway->shouldReceive('refund')
            ->once()
            ->andReturn(new PaymentGatewayRefund('rfnd_pending', 5000, 'queued'));

        $refund = $this->processorWithGateway($gateway)->createAndProcess($order, 5000, 'Test', $approver->id);

        $this->assertSame(Refund::STATUS_PROCESSING, $refund->status);
    }

    public function test_throws_when_the_order_has_no_successful_payment(): void
    {
        $order = Order::factory()->create();
        Payment::factory()->for($order)->create(['status' => Payment::STATUS_FAILED]);
        $approver = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $gateway = \Mockery::mock(PaymentGateway::class);
        $gateway->shouldNotReceive('refund');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Order has no successful payment to refund.');

        $this->processorWithGateway($gateway)->createAndProcess($order, 5000, 'Test', $approver->id);
    }

    public function test_throws_when_the_payment_has_no_verified_transaction(): void
    {
        $order = Order::factory()->create();
        $payment = Payment::factory()->for($order)->create(['status' => Payment::STATUS_SUCCESS]);
        PaymentTransaction::factory()->for($payment)->create([
            'provider_payment_id' => 'pay_unverified',
            'signature_verified' => false,
        ]);
        $approver = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $gateway = \Mockery::mock(PaymentGateway::class);
        $gateway->shouldNotReceive('refund');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No verified payment transaction found for refund.');

        $this->processorWithGateway($gateway)->createAndProcess($order, 5000, 'Test', $approver->id);
    }

    public function test_approve_reuses_the_refunds_own_payment_and_updates_it_via_the_gateway(): void
    {
        $order = $this->orderWithVerifiedPayment();
        $payment = $order->payments()->first();
        $approver = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $refund = Refund::factory()->create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'status' => Refund::STATUS_PENDING,
            'amount_paise' => 3000,
        ]);

        $gateway = \Mockery::mock(PaymentGateway::class);
        $gateway->shouldReceive('refund')
            ->once()
            ->with('pay_verified_123', 3000)
            ->andReturn(new PaymentGatewayRefund('rfnd_def', 3000, 'captured'));

        $updated = $this->processorWithGateway($gateway)->approve($refund, $approver->id);

        $this->assertSame(Refund::STATUS_COMPLETED, $updated->status);
        $this->assertSame('rfnd_def', $updated->provider_refund_id);
    }
}
