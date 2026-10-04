<?php

namespace App\Http\Controllers\Admin;

use App\Constants\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderActivityLog;
use App\Models\OrderStatusHistory;
use App\Models\OtpVerification;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\Activity\ActivityLogger;
use App\Services\Notifications\NotificationService;
use App\Services\Orders\OrderOtpVerificationService;
use App\Services\Refunds\RefundProcessor;
use App\StateMachines\OrderStateMachine;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

/**
 * Orders are created only by the customer booking flow — there is no
 * create/edit form here. Admin/ops act through the named actions below,
 * each of which calls OrderStateMachine::transition() (or RefundProcessor)
 * directly so every change is validated and recorded in
 * order_status_history exactly like the customer/partner-facing flows.
 * Mirrors the removed Filament OrderResource.
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly OrderStateMachine $stateMachine,
        private readonly RefundProcessor $refundProcessor,
        private readonly OrderOtpVerificationService $otpVerification,
        private readonly ActivityLogger $activityLogger,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): View
    {
        $orders = $this->orders->paginate(15, $request->only(['status', 'trashed', 'booking_date', 'search']));

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => $request->only(['status', 'trashed', 'booking_date', 'search']),
            'statuses' => OrderStatus::ALL,
        ]);
    }

    /**
     * "Recheck Orders" — a focused daily audit view for ops: only orders
     * that already have a partner matched (partner_id set), defaulting to
     * today's booking date, with a date picker and search box to look at
     * any other day. Unassigned orders (still waiting for a rider to accept
     * from the mobile app) never show up here — see the "Awaiting partner
     * assignment" stat/link on the dashboard for those.
     */
    public function recheck(Request $request): View
    {
        $bookingDate = $request->query('booking_date') ?: today()->toDateString();

        $orders = $this->orders->paginate(15, [
            'assigned_only' => true,
            'booking_date' => $bookingDate,
            'search' => $request->query('search'),
        ]);

        return view('admin.orders.recheck', [
            'orders' => $orders,
            'filters' => [
                'booking_date' => $bookingDate,
                'search' => $request->query('search'),
            ],
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['customer', 'route.originStation', 'route.destinationStation', 'partner.user', 'statusHistory' => function ($query) {
            $query->orderBy('created_at')->orderBy('id');
        }, 'payments', 'refunds', 'otpVerifications' => function ($query) {
            $query->orderByDesc('id');
        }, 'activityLogs' => function ($query) {
            $query->orderBy('created_at')->orderBy('id');
        }, 'activityLogs.actor']);

        return view('admin.orders.show', [
            'order' => $order,
            'eligiblePartners' => ! OrderStatus::isTerminal($order->status)
                ? $this->orders->eligiblePartners($order)
                : collect(),
            'latestPickupOtp' => $order->otpVerifications->firstWhere('purpose', OtpVerification::PURPOSE_PICKUP),
            'latestDeliveryOtp' => $order->otpVerifications->firstWhere('purpose', OtpVerification::PURPOSE_DELIVERY),
        ]);
    }

    public function generateOtp(Order $order, string $purpose): RedirectResponse
    {
        abort_unless(AdminAccess::canManageOrders(), 403);

        if (! in_array($purpose, [OtpVerification::PURPOSE_PICKUP, OtpVerification::PURPOSE_DELIVERY], true)) {
            abort(404);
        }

        try {
            [$verification, $plainOtp] = $purpose === OtpVerification::PURPOSE_PICKUP
                ? $this->otpVerification->adminGeneratePickup($order)
                : $this->otpVerification->adminGenerateDelivery($order);

            return redirect()->route('admin.orders.show', $order)
                ->with('status', ucfirst($purpose)." OTP generated: {$plainOtp} (expires {$verification->expires_at->format('H:i:s')}). Visible here for testing only — it is not shown again.")
                ->with('generatedOtp', [
                    'purpose' => $purpose,
                    'otp' => $plainOtp,
                    'phone' => $verification->phone,
                    'expires_at' => $verification->expires_at,
                ]);
        } catch (RuntimeException $e) {
            return redirect()->route('admin.orders.show', $order)->with('error', 'Could not generate OTP: '.$e->getMessage());
        }
    }

    /**
     * Assigns or reassigns the delivery partner on an order. Allowed for any
     * non-terminal order (docs fraud-prevention addendum keeps COMPLETED/
     * CANCELLED/REFUNDED immutable) — not just first-time assignment on a
     * RIDER_ASSIGNMENT_PENDING order — so admin/ops can correct a bad match
     * or hand an order to a different rider mid-flow. Every reassignment is
     * written to the activity log with the previous partner id for audit,
     * and the newly-assigned partner is pushed a notification.
     */
    public function assignPartner(Request $request, Order $order): RedirectResponse
    {
        abort_unless(AdminAccess::canManageOrders(), 403);

        if (OrderStatus::isTerminal($order->status)) {
            return redirect()->route('admin.orders.show', $order)->with('error', 'Order is in a terminal state and can no longer be (re)assigned.');
        }

        $data = $request->validate([
            'partner_id' => ['required', 'integer', 'exists:delivery_partners,id'],
        ]);

        $previousPartnerId = $order->partner_id;
        $newPartnerId = (int) $data['partner_id'];

        if ($previousPartnerId === $newPartnerId) {
            return redirect()->route('admin.orders.show', $order)->with('error', 'That partner is already assigned to this order.');
        }

        $order->forceFill(['partner_id' => $newPartnerId])->save();

        $this->activityLogger->log(
            $order,
            $previousPartnerId === null ? OrderActivityLog::EVENT_PARTNER_MATCHED : OrderActivityLog::EVENT_PARTNER_REASSIGNED,
            OrderActivityLog::ACTOR_ADMIN,
            auth('web')->id(),
            metadata: ['partner_id' => $newPartnerId, 'previous_partner_id' => $previousPartnerId],
        );

        $newPartner = DeliveryPartner::query()->find($newPartnerId);
        if ($newPartner !== null) {
            $this->notifications->notifyUser(
                $newPartner->user_id,
                'new_order_assigned',
                'New delivery assignment',
                "Order {$order->booking_reference} has been assigned to you. Open the app to accept it.",
                ['order_id' => $order->id],
                Notification::CHANNEL_PUSH,
            );
        }

        $message = $previousPartnerId === null ? 'Partner assigned.' : 'Partner reassigned.';

        return redirect()->route('admin.orders.show', $order)
            ->with('status', "{$message} They still need to accept the assignment in their app.");
    }

    public function forceCancel(Request $request, Order $order): RedirectResponse
    {
        abort_unless(AdminAccess::canManageOrders(), 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->stateMachine->transition(
                $order,
                OrderStatus::CANCELLED,
                OrderStatusHistory::ACTOR_ADMIN,
                auth()->id(),
                [
                    'cancelled_at' => now(),
                    'cancellation_reason' => $data['reason'],
                    'cancelled_by' => Order::CANCELLED_BY_ADMIN,
                ],
            );

            return redirect()->route('admin.orders.show', $order)->with('status', 'Order cancelled.');
        } catch (InvalidOrderTransitionException $e) {
            return redirect()->route('admin.orders.show', $order)->with('error', $e->getMessage());
        }
    }

    public function markDisputed(Request $request, Order $order): RedirectResponse
    {
        abort_unless(AdminAccess::canManageOrders(), 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->stateMachine->transition(
                $order,
                OrderStatus::DISPUTED,
                OrderStatusHistory::ACTOR_ADMIN,
                auth()->id(),
                [],
                ['reason' => $data['reason']],
            );

            return redirect()->route('admin.orders.show', $order)->with('status', 'Order marked disputed.');
        } catch (InvalidOrderTransitionException $e) {
            return redirect()->route('admin.orders.show', $order)->with('error', $e->getMessage());
        }
    }

    public function approveRefund(Request $request, Order $order): RedirectResponse
    {
        abort_unless(AdminAccess::canApproveRefunds(), 403);

        if ($order->status !== OrderStatus::REFUND_PENDING) {
            return redirect()->route('admin.orders.show', $order)->with('error', 'Order is not awaiting refund approval.');
        }

        $data = $request->validate([
            'amount_paise' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->refundProcessor->createAndProcess(
                $order,
                (int) $data['amount_paise'],
                $data['reason'],
                auth()->id(),
            );

            $this->stateMachine->transition(
                $order,
                OrderStatus::REFUNDED,
                OrderStatusHistory::ACTOR_ADMIN,
                auth()->id(),
            );

            return redirect()->route('admin.orders.show', $order)->with('status', 'Refund processed.');
        } catch (RuntimeException|InvalidOrderTransitionException $e) {
            return redirect()->route('admin.orders.show', $order)->with('error', 'Refund could not be processed: '.$e->getMessage());
        }
    }
}
