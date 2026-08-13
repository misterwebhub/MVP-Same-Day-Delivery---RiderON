<?php

namespace App\Http\Controllers\Api\V1;

use App\Constants\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\VerifyPaymentRequest;
use App\Models\OrderActivityLog;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Services\Activity\ActivityLogger;
use App\Services\Payments\PaymentConfirmationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentConfirmationService $confirmationService,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    public function verify(VerifyPaymentRequest $request, Payment $payment): JsonResponse
    {
        abort_unless($payment->order->customer_id === $request->user()->id, 403);

        $this->confirmationService->confirm(
            payment: $payment,
            eventType: PaymentTransaction::EVENT_CHECKOUT_VERIFY,
            signaturePayload: [
                'razorpay_order_id' => $request->string('razorpay_order_id')->toString(),
                'razorpay_payment_id' => $request->string('razorpay_payment_id')->toString(),
                'razorpay_signature' => $request->string('razorpay_signature')->toString(),
                'force_failure' => $request->boolean('force_failure'),
            ],
            providerPaymentId: $request->string('razorpay_payment_id')->toString(),
            rawPayload: $request->all(),
            targetOrderStatus: OrderStatus::BOOKED,
        );

        $payment->refresh()->load('order');

        $this->activityLogger->log(
            $payment->order,
            OrderActivityLog::EVENT_PAYMENT_VERIFIED,
            OrderActivityLog::ACTOR_CUSTOMER,
            $request->user()->id,
            $request,
            metadata: ['payment_status' => $payment->status],
        );

        return $this->success([
            'payment_status' => $payment->status,
            'order_status' => $payment->order->status,
        ], 'Payment verified.');
    }

    /**
     * Server-to-server fallback (docs/05) — authoritative even if the client
     * never calls verify(). No auth/idempotency-header middleware: the
     * provider signature is the authentication, and PaymentConfirmationService
     * dedupes on (provider_payment_id, event_type) instead of a client header.
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();
        $eventType = $payload['event'] ?? 'unknown';
        $providerOrderId = data_get($payload, 'payload.payment.entity.order_id');
        $providerPaymentId = data_get($payload, 'payload.payment.entity.id');

        $payment = Payment::query()->where('provider_order_id', $providerOrderId)->first();

        if ($payment === null) {
            return $this->success(null, 'Ignored: no matching payment.');
        }

        $targetOrderStatus = match ($eventType) {
            'payment.captured' => OrderStatus::BOOKED,
            'payment.failed' => OrderStatus::PAYMENT_FAILED,
            default => null,
        };

        $this->confirmationService->confirm(
            payment: $payment,
            eventType: $eventType,
            signaturePayload: [
                'body' => $request->getContent(),
                'signature' => $request->header('X-Razorpay-Signature'),
                'force_failure' => (bool) data_get($payload, 'force_failure', false),
            ],
            providerPaymentId: $providerPaymentId,
            rawPayload: $payload,
            targetOrderStatus: $targetOrderStatus,
        );

        return $this->success(null, 'Webhook processed.');
    }

    public function status(Request $request, Payment $payment): JsonResponse
    {
        abort_unless($payment->order->customer_id === $request->user()->id, 403);

        return $this->success([
            'payment_status' => $payment->status,
            'order_status' => $payment->order->status,
        ]);
    }
}
