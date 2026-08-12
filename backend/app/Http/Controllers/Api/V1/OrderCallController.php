<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Services\Call\CallProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Wires the existing CallProvider abstraction (app/Services/Call, bound by
 * CallServiceProvider to Mock or Exotel per CALL_DRIVER) to an actual route.
 * Neither party's real number is ever returned to the partner app — this
 * endpoint tells the provider to bridge the two real numbers server-side
 * and returns only a call session id, per docs/06's call-via-proxy design.
 * Under CALL_DRIVER=mock (dev default) no real call is placed; the attempt
 * is logged (MockCallProvider) and a success envelope with a mock call id
 * is returned — same "log, don't fabricate" convention as MockSmsProvider
 * and MockPaymentGateway.
 */
class OrderCallController extends Controller
{
    public function __construct(private readonly CallProvider $callProvider)
    {
    }

    public function initiate(Request $request, Order $order, string $target): JsonResponse
    {
        if (! in_array($target, ['sender', 'receiver'], true)) {
            throw ValidationException::withMessages(['target' => ['Target must be sender or receiver.']]);
        }

        $partner = $this->requirePartner($request);
        abort_unless($order->partner_id === $partner->id, 403);

        $toPhone = $target === 'sender' ? $order->sender_phone : $order->receiver_phone;

        $result = $this->callProvider->initiateMaskedCall($partner->user->phone, $toPhone);

        if (! $result->success) {
            return $this->success([
                'success' => false,
                'provider_call_sid' => null,
            ], $result->error ?? 'Could not place the call.');
        }

        return $this->success([
            'success' => true,
            'provider_call_sid' => $result->providerCallSid,
        ], 'Call initiated.');
    }

    private function requirePartner(Request $request): DeliveryPartner
    {
        $partner = $request->user()->deliveryPartner;

        abort_unless($partner !== null, 403);

        return $partner;
    }
}
