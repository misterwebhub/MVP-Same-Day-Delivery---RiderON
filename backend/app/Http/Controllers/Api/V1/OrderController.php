<?php

namespace App\Http\Controllers\Api\V1;

use App\Constants\OrderStatus;
use App\Exceptions\OrderNotCancellableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\CancelOrderRequest;
use App\Http\Requests\Orders\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderDeclaration;
use App\Models\OrderStatusHistory;
use App\Models\Parcel;
use App\Models\Payment;
use App\Models\ProhibitedItemsVersion;
use App\Models\OrderActivityLog;
use App\Models\Refund;
use App\Models\Route;
use App\Models\RouteSchedule;
use App\Services\Activity\ActivityLogger;
use App\Services\Catalog\RouteScheduleAvailabilityService;
use App\Services\Orders\BookingReferenceGenerator;
use App\Services\Orders\OrderCancellationPolicy;
use App\Services\PaymentGateway\PaymentGateway;
use App\Services\PricingEngine;
use App\StateMachines\OrderStateMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    private const ORDER_RELATIONS = [
        'route.originStation',
        'route.destinationStation',
        'routeSchedule',
        'parcel.images',
        'payments',
        'otpVerifications',
        // Once a partner accepts (partner_id set), the customer app shows
        // their name/phone/vehicle + the route they're carrying it on.
        'partner.user',
    ];

    public function __construct(
        private readonly PricingEngine $pricingEngine,
        private readonly RouteScheduleAvailabilityService $availabilityService,
        private readonly BookingReferenceGenerator $bookingReferenceGenerator,
        private readonly OrderCancellationPolicy $cancellationPolicy,
        private readonly OrderStateMachine $stateMachine,
        private readonly PaymentGateway $paymentGateway,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('customer_id', $request->user()->id)
            ->with(self::ORDER_RELATIONS)
            ->orderByDesc('id')
            ->paginate((int) $request->integer('per_page', 20));

        return $this->success([
            'items' => OrderResource::collection($orders->items()),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $quote = $this->pricingEngine->verifyQuoteToken($request->string('quote_token')->toString());

        $route = Route::query()->findOrFail($quote['route_id']);
        abort_unless($route->is_active, 404);

        $schedule = RouteSchedule::query()->findOrFail($quote['route_schedule_id']);
        $bookingDate = $request->string('booking_date')->toString();

        $order = Cache::lock("schedule-capacity:{$schedule->id}:{$bookingDate}", 10)
            ->block(10, function () use ($request, $route, $schedule, $bookingDate, $quote) {
                $this->availabilityService->assertBookable($route, $schedule, $bookingDate);

                return DB::transaction(fn () => $this->createOrder($request, $route, $schedule, $bookingDate, $quote));
            });

        $order->load(self::ORDER_RELATIONS);

        return $this->success(new OrderResource($order), 'Order created.', 201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->customer_id === $request->user()->id, 403);

        $order->load(self::ORDER_RELATIONS);

        return $this->success(new OrderResource($order));
    }

    public function cancel(CancelOrderRequest $request, Order $order): JsonResponse
    {
        abort_unless($order->customer_id === $request->user()->id, 403);

        $decision = $this->cancellationPolicy->evaluate($order);

        if ($decision->adminOnly) {
            throw new OrderNotCancellableException('This order requires admin/ops approval to cancel at its current stage.');
        }

        if (! $decision->cancellable) {
            throw new OrderNotCancellableException();
        }

        $updated = $this->stateMachine->transition($order, OrderStatus::CANCELLED, OrderStatusHistory::ACTOR_CUSTOMER, $request->user()->id, [
            'cancelled_at' => now(),
            'cancellation_reason' => $request->input('reason'),
            'cancelled_by' => Order::CANCELLED_BY_CUSTOMER,
        ]);

        if ($decision->requiresRefund && $decision->refundPercentage > 0) {
            $this->processRefund($updated, $decision->refundPercentage);
        }

        $this->activityLogger->log(
            $updated,
            OrderActivityLog::EVENT_ORDER_CANCELLED,
            OrderActivityLog::ACTOR_CUSTOMER,
            $request->user()->id,
            $request,
            metadata: ['reason' => $request->input('reason')],
        );

        $updated->load(self::ORDER_RELATIONS);

        return $this->success(new OrderResource($updated), 'Order cancelled.');
    }

    /**
     * @param  array<string, mixed>  $quote  Trusted payload from PricingEngine::verifyQuoteToken().
     */
    private function createOrder(Request $request, Route $route, RouteSchedule $schedule, string $bookingDate, array $quote): Order
    {
        $bookingReference = $this->bookingReferenceGenerator->generate();
        $idempotencyKey = $request->header('Idempotency-Key');

        $route->loadMissing(['originStation.city', 'destinationStation.city']);

        [$pickupAddressText, $pickupLatitude, $pickupLongitude] = $this->resolveManualAddress(
            $route->originStation?->city?->name,
            $request->input('pickup_address_text'),
            $request->input('pickup_latitude'),
            $request->input('pickup_longitude'),
        );

        [$deliveryAddressText, $deliveryLatitude, $deliveryLongitude] = $this->resolveManualAddress(
            $route->destinationStation?->city?->name,
            $request->input('delivery_address_text'),
            $request->input('delivery_latitude'),
            $request->input('delivery_longitude'),
        );

        $order = Order::create([
            'booking_reference' => $bookingReference,
            'customer_id' => $request->user()->id,
            'route_id' => $route->id,
            'route_schedule_id' => $schedule->id,
            'status' => OrderStatus::PAYMENT_PENDING,
            'booking_date' => $bookingDate,
            'sender_name' => $request->string('sender_name')->toString(),
            'sender_phone' => $request->string('sender_phone')->toString(),
            'sender_landmark' => $request->input('sender_landmark'),
            'receiver_name' => $request->string('receiver_name')->toString(),
            'receiver_phone' => $request->string('receiver_phone')->toString(),
            'receiver_landmark' => $request->input('receiver_landmark'),
            'pickup_address_text' => $pickupAddressText,
            'pickup_latitude' => $pickupLatitude,
            'pickup_longitude' => $pickupLongitude,
            'delivery_address_text' => $deliveryAddressText,
            'delivery_latitude' => $deliveryLatitude,
            'delivery_longitude' => $deliveryLongitude,
            'price_breakdown' => $quote['breakdown'],
            'total_amount_paise' => $quote['total_amount_paise'],
            'currency' => 'INR',
            'idempotency_key' => $idempotencyKey,
        ]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => null,
            'to_status' => OrderStatus::PAYMENT_PENDING,
            'changed_by_type' => OrderStatusHistory::ACTOR_CUSTOMER,
            'changed_by_id' => $request->user()->id,
            'created_at' => now(),
        ]);

        Parcel::create([
            'order_id' => $order->id,
            'parcel_type' => $request->string('parcel_type')->toString(),
            'weight_slab' => $quote['weight_slab'],
            'quantity' => $quote['quantity'],
            'declared_value_paise' => $quote['declared_value_paise'],
            'special_instructions' => $request->input('special_instructions'),
        ]);

        OrderDeclaration::create([
            'order_id' => $order->id,
            'prohibited_items_version_id' => ProhibitedItemsVersion::query()->orderByDesc('published_at')->value('id'),
            'accepted_at' => now(),
            'ip_address' => $request->ip(),
            'device_info' => $request->userAgent(),
        ]);

        $order->forceFill(['prohibited_items_declared_at' => now()])->save();

        $this->activityLogger->log(
            $order,
            OrderActivityLog::EVENT_ORDER_BOOKED,
            OrderActivityLog::ACTOR_CUSTOMER,
            $request->user()->id,
            $request,
        );

        $gatewayOrder = $this->paymentGateway->createOrder($quote['total_amount_paise'], 'INR', $bookingReference);

        Payment::create([
            'order_id' => $order->id,
            'provider' => config('services.payment_driver'),
            'provider_order_id' => $gatewayOrder->providerOrderId,
            'amount_paise' => $quote['total_amount_paise'],
            'currency' => 'INR',
            'status' => Payment::STATUS_CREATED,
            'idempotency_key' => $idempotencyKey,
        ]);

        return $order;
    }

    /**
     * Manual address entry (pickup OR delivery) is only ever trusted/stored
     * when the relevant end of the route (origin for pickup, destination for
     * delivery) belongs to a city in config('parcel.manual_address_cities')
     * (currently Kanpur). Anywhere else — even if the client submitted these
     * fields — they're silently dropped, so the order falls back to the
     * fixed station's own coordinates everywhere downstream (partner
     * distance calc, maps link). See OrderController::resolveManualAddress().
     *
     * @return array{0: ?string, 1: ?float, 2: ?float} [address_text, lat, lng]
     */
    private function resolveManualAddress(?string $cityName, mixed $addressText, mixed $latitude, mixed $longitude): array
    {
        $allowed = $cityName !== null && collect(config('parcel.manual_address_cities', []))
            ->contains(fn ($city) => strcasecmp($city, $cityName) === 0);

        if (! $allowed) {
            return [null, null, null];
        }

        if ($addressText === null && $latitude === null && $longitude === null) {
            return [null, null, null];
        }

        return [
            $addressText !== null ? (string) $addressText : null,
            $latitude !== null ? (float) $latitude : null,
            $longitude !== null ? (float) $longitude : null,
        ];
    }

    private function processRefund(Order $order, int $refundPercentage): void
    {
        $payment = $order->payments()->where('status', Payment::STATUS_SUCCESS)->latest('id')->first();

        if ($payment === null) {
            return;
        }

        $providerPaymentId = $payment->transactions()
            ->where('signature_verified', true)
            ->whereNotNull('provider_payment_id')
            ->latest('id')
            ->value('provider_payment_id');

        if ($providerPaymentId === null) {
            return;
        }

        $refundAmountPaise = (int) round($payment->amount_paise * $refundPercentage / 100);

        if ($refundAmountPaise <= 0) {
            return;
        }

        $gatewayRefund = $this->paymentGateway->refund($providerPaymentId, $refundAmountPaise);

        Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'requested_by' => Refund::REQUESTED_BY_CUSTOMER,
            'reason' => 'Order cancellation',
            'amount_paise' => $refundAmountPaise,
            'status' => Refund::STATUS_PROCESSING,
            'provider_refund_id' => $gatewayRefund->providerRefundId,
        ]);
    }
}
