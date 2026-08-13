<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\VerifyOrderOtpRequest;
use App\Models\Order;
use App\Services\Orders\OrderOtpVerificationService;
use Illuminate\Http\JsonResponse;

class OrderOtpController extends Controller
{
    public function __construct(
        private readonly OrderOtpVerificationService $otpVerificationService,
    ) {
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
