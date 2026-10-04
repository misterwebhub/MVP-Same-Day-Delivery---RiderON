<?php

namespace App\Http\Controllers\Admin;

use App\Constants\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Replicates the old Filament dashboard widgets' exact query logic
 * (TodaysBookingsWidget, DeliveryFunnelWidget, ActiveRidersWidget) as
 * plain stat cards — same numbers, no Livewire.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $today = today();

        $todaysOrders = Order::query()->whereDate('booking_date', $today);

        $bookingsCount = (clone $todaysOrders)->count();

        $revenuePaise = (clone $todaysOrders)
            ->whereNotIn('status', [OrderStatus::CANCELLED, OrderStatus::PAYMENT_FAILED])
            ->sum('total_amount_paise');

        $pendingAssignment = (clone $todaysOrders)
            ->where('status', OrderStatus::RIDER_ASSIGNMENT_PENDING)
            ->count();

        // All-time (not just today) count of orders still waiting for a
        // rider to accept from the mobile app — no partner is ever
        // auto-assigned on booking (see PartnerAssignmentService), so this
        // is the true size of the unassigned pool every eligible rider can
        // currently see and claim from.
        $totalUnassigned = Order::query()
            ->where('status', OrderStatus::RIDER_ASSIGNMENT_PENDING)
            ->whereNull('partner_id')
            ->count();

        $countFor = fn (array $statuses): int => Order::query()
            ->whereDate('booking_date', $today)
            ->whereIn('status', $statuses)
            ->count();

        $funnel = [
            'booked' => $countFor([OrderStatus::BOOKED]),
            'rider_assigned' => $countFor([
                OrderStatus::RIDER_ASSIGNMENT_PENDING,
                OrderStatus::RIDER_ASSIGNED,
                OrderStatus::WAITING_FOR_PICKUP,
                OrderStatus::RIDER_ARRIVED_PICKUP,
                OrderStatus::PICKUP_OTP_PENDING,
            ]),
            'in_transit' => $countFor([
                OrderStatus::PICKED_UP,
                OrderStatus::IN_TRANSIT,
                OrderStatus::ARRIVED_DESTINATION,
                OrderStatus::WAITING_FOR_RECEIVER,
                OrderStatus::DELIVERY_OTP_PENDING,
            ]),
            'delivered_completed' => $countFor([
                OrderStatus::DELIVERED,
                OrderStatus::COMPLETED,
            ]),
            'exceptions' => $countFor([
                OrderStatus::FAILED_DELIVERY,
                OrderStatus::DISPUTED,
                OrderStatus::REFUND_PENDING,
            ]),
        ];

        $activePartners = DeliveryPartner::query()->where('is_active', true)->count();

        $verifiedPartners = DeliveryPartner::query()
            ->where('is_active', true)
            ->where('verification_status', DeliveryPartner::VERIFICATION_VERIFIED)
            ->count();

        $onDeliveryPartners = Order::query()
            ->whereNotNull('partner_id')
            ->whereNotIn('status', OrderStatus::TERMINAL)
            ->distinct('partner_id')
            ->count('partner_id');

        return view('admin.dashboard', [
            'user' => $request->user('web'),
            'bookingsCount' => $bookingsCount,
            'revenuePaise' => $revenuePaise,
            'pendingAssignment' => $pendingAssignment,
            'totalUnassigned' => $totalUnassigned,
            'funnel' => $funnel,
            'activePartners' => $activePartners,
            'verifiedPartners' => $verifiedPartners,
            'onDeliveryPartners' => $onDeliveryPartners,
        ]);
    }
}
