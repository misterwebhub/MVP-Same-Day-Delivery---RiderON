<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\VerifyOrderOtpRequest;
use App\Models\Order;
use App\Services\Orders\OrderOtpVerificationService;
use App\Services\Otp\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderOtpController extends Controller
{
    public function __construct(
        private readonly OrderOtpVerificationService $otpVerificationService,
        private readonly OtpService $otpService,
    ) {
    }

    /**
     * Customer-initiated — the customer's app is the only client that shows
     * both OTP cards (docs/05's "UI separation" section), so resend is
     * triggered from there regardless of which purpose is being resent.
     */
    public function resend(Request $request, Order $order, string $purpose): JsonResponse
    {
        abort_unless($order->customer_id === $request->user()->id, 403);

        $otp = $purpose === 'pickup'
            ? $this->otpVerificationService->resendPickup($order)
            : $this->otpVerificationService->resendDelivery($order);

        return $this->success([
            'code' => $this->otpService->peekCachedPlainOtp($otp),
            'expires_at' => $otp->expires_at->toIso8601String(),
            'resend_count' => $otp->resend_count,
        ], 'OTP resent.');
    }

    /**
     * Partner-only, and only the partner assigned to this order (docs/05).
     */
    public function verify(VerifyOrderOtpRequest $request, Order $order, string $purpose): JsonResponse
    {
        $partner = $request->user()->deliveryPartner;
        abort_unless($partner !== null && $order->partner_id === $partner->id, 403);

        $inputOtp = $request->string('otp')->toString();

        $updated = $purpose === 'pickup'
            ? $this->otpVerificationService->verifyPickup($order, $partner, $inputOtp, $request)
            : $this->otpVerificationService->verifyDelivery($order, $partner, $inputOtp, $request);

        return $this->success(['order_status' => $updated->status], 'OTP verified.');
    }
}
