<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Real earnings, computed from actual completed orders — never fabricated.
 * A partner's per-order earning is a configured share (config/pricing.php
 * partner_commission_percent) of that order's real total_amount_paise,
 * recognised at the moment the order reaches COMPLETED (Order::completed_at).
 * There is no separate ledger/payout table yet (see docs/06) — this is a
 * live read computed from Orders, which is honest about being derived data
 * rather than a settled payout record.
 */
class PartnerEarningsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $partner = $this->requirePartner($request);
        $commissionPercent = (int) config('pricing.partner_commission_percent');

        $completedQuery = Order::query()
            ->where('partner_id', $partner->id)
            ->where('status', 'COMPLETED')
            ->whereNotNull('completed_at');

        $todayPaise = (clone $completedQuery)->whereDate('completed_at', now()->toDateString())->sum('total_amount_paise');
        $weekPaise = (clone $completedQuery)->where('completed_at', '>=', now()->startOfWeek())->sum('total_amount_paise');
        $monthPaise = (clone $completedQuery)->where('completed_at', '>=', now()->startOfMonth())->sum('total_amount_paise');

        $recent = (clone $completedQuery)
            ->orderByDesc('completed_at')
            ->limit(20)
            ->get(['id', 'booking_reference', 'completed_at', 'total_amount_paise'])
            ->map(fn (Order $order) => [
                'order_id' => $order->id,
                'booking_reference' => $order->booking_reference,
                'completed_at' => $order->completed_at?->toIso8601String(),
                'earnings_paise' => $this->applyCommission((int) $order->total_amount_paise, $commissionPercent),
            ])
            ->values();

        return $this->success([
            'commission_percent' => $commissionPercent,
            'today_earnings_paise' => $this->applyCommission((int) $todayPaise, $commissionPercent),
            'week_earnings_paise' => $this->applyCommission((int) $weekPaise, $commissionPercent),
            'month_earnings_paise' => $this->applyCommission((int) $monthPaise, $commissionPercent),
            'completed_deliveries_count' => $partner->completed_deliveries_count,
            'recent' => $recent,
        ]);
    }

    private function applyCommission(int $totalPaise, int $commissionPercent): int
    {
        return (int) round($totalPaise * $commissionPercent / 100);
    }

    private function requirePartner(Request $request): DeliveryPartner
    {
        $partner = $request->user()->deliveryPartner;

        abort_unless($partner !== null, 403);

        return $partner;
    }
}
