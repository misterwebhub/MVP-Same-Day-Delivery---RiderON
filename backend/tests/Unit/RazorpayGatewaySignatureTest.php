<?php

namespace Tests\Unit;

use App\Services\PaymentGateway\RazorpayGateway;
use Tests\TestCase;

class RazorpayGatewaySignatureTest extends TestCase
{
    private RazorpayGateway $gateway;

    private const KEY_SECRET = 'test_key_secret';

    private const WEBHOOK_SECRET = 'test_webhook_secret';

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new RazorpayGateway('rzp_test_key', self::KEY_SECRET, self::WEBHOOK_SECRET);
    }

    public function test_accepts_a_correctly_signed_checkout_callback_payload(): void
    {
        $orderId = 'order_abc123';
        $paymentId = 'pay_xyz789';
        $signature = hash_hmac('sha256', $orderId.'|'.$paymentId, self::KEY_SECRET);

        $this->assertTrue($this->gateway->verifySignature([
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $signature,
        ]));
    }

    public function test_rejects_a_checkout_callback_payload_with_a_tampered_signature(): void
    {
        $orderId = 'order_abc123';
        $paymentId = 'pay_xyz789';

        $this->assertFalse($this->gateway->verifySignature([
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => 'deadbeef',
        ]));
    }

    public function test_rejects_a_checkout_callback_payload_signed_with_the_wrong_secret(): void
    {
        $orderId = 'order_abc123';
        $paymentId = 'pay_xyz789';
        $signature = hash_hmac('sha256', $orderId.'|'.$paymentId, 'some_other_secret');

        $this->assertFalse($this->gateway->verifySignature([
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $signature,
        ]));
    }

    public function test_accepts_a_correctly_signed_webhook_payload(): void
    {
        $body = json_encode(['event' => 'payment.captured', 'payload' => ['id' => 'pay_xyz789']]);
        $signature = hash_hmac('sha256', $body, self::WEBHOOK_SECRET);

        $this->assertTrue($this->gateway->verifySignature([
            'body' => $body,
            'signature' => $signature,
        ]));
    }

    public function test_rejects_a_webhook_payload_with_a_tampered_body(): void
    {
        $body = json_encode(['event' => 'payment.captured', 'payload' => ['id' => 'pay_xyz789']]);
        $signature = hash_hmac('sha256', $body, self::WEBHOOK_SECRET);

        $tamperedBody = json_encode(['event' => 'payment.captured', 'payload' => ['id' => 'pay_attacker']]);

        $this->assertFalse($this->gateway->verifySignature([
            'body' => $tamperedBody,
            'signature' => $signature,
        ]));
    }

    public function test_rejects_a_webhook_payload_signed_with_the_checkout_key_secret_instead_of_the_webhook_secret(): void
    {
        $body = json_encode(['event' => 'payment.captured']);
        $signature = hash_hmac('sha256', $body, self::KEY_SECRET);

        $this->assertFalse($this->gateway->verifySignature([
            'body' => $body,
            'signature' => $signature,
        ]));
    }

    public function test_rejects_a_payload_that_matches_neither_known_shape(): void
    {
        $this->assertFalse($this->gateway->verifySignature([
            'unexpected_key' => 'value',
        ]));
    }
}
