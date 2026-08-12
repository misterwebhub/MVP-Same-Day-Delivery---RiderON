<?php

namespace App\Constants;

/**
 * Canonical order status values. See docs/04-state-machine.md for the
 * authoritative state diagram and transition table that this mirrors.
 */
final class OrderStatus
{
    public const DRAFT = 'DRAFT';

    public const PAYMENT_PENDING = 'PAYMENT_PENDING';

    public const PAYMENT_FAILED = 'PAYMENT_FAILED';

    public const BOOKED = 'BOOKED';

    public const RIDER_ASSIGNMENT_PENDING = 'RIDER_ASSIGNMENT_PENDING';

    public const RIDER_ASSIGNED = 'RIDER_ASSIGNED';

    public const WAITING_FOR_PICKUP = 'WAITING_FOR_PICKUP';

    public const RIDER_ARRIVED_PICKUP = 'RIDER_ARRIVED_PICKUP';

    public const PICKUP_OTP_PENDING = 'PICKUP_OTP_PENDING';

    public const PICKED_UP = 'PICKED_UP';

    public const IN_TRANSIT = 'IN_TRANSIT';

    public const ARRIVED_DESTINATION = 'ARRIVED_DESTINATION';

    public const WAITING_FOR_RECEIVER = 'WAITING_FOR_RECEIVER';

    public const DELIVERY_OTP_PENDING = 'DELIVERY_OTP_PENDING';

    public const DELIVERED = 'DELIVERED';

    public const COMPLETED = 'COMPLETED';

    public const CANCELLED = 'CANCELLED';

    public const REFUND_PENDING = 'REFUND_PENDING';

    public const REFUNDED = 'REFUNDED';

    public const FAILED_DELIVERY = 'FAILED_DELIVERY';

    public const DISPUTED = 'DISPUTED';

    public const ALL = [
        self::DRAFT,
        self::PAYMENT_PENDING,
        self::PAYMENT_FAILED,
        self::BOOKED,
        self::RIDER_ASSIGNMENT_PENDING,
        self::RIDER_ASSIGNED,
        self::WAITING_FOR_PICKUP,
        self::RIDER_ARRIVED_PICKUP,
        self::PICKUP_OTP_PENDING,
        self::PICKED_UP,
        self::IN_TRANSIT,
        self::ARRIVED_DESTINATION,
        self::WAITING_FOR_RECEIVER,
        self::DELIVERY_OTP_PENDING,
        self::DELIVERED,
        self::COMPLETED,
        self::CANCELLED,
        self::REFUND_PENDING,
        self::REFUNDED,
        self::FAILED_DELIVERY,
        self::DISPUTED,
    ];

    /** Terminal states — no further transitions are possible. */
    public const TERMINAL = [self::COMPLETED, self::CANCELLED, self::REFUNDED];

    /**
     * States from which a customer can self-service cancel, per
     * docs/04-state-machine.md's cancellation eligibility table.
     * The actual fee/refund percentage for each is admin-configurable
     * via app_settings (`cancellation.*` keys), not hard-coded here.
     */
    public const SELF_SERVICE_CANCELLABLE = [
        self::PAYMENT_PENDING,
        self::PAYMENT_FAILED,
        self::BOOKED,
        self::RIDER_ASSIGNMENT_PENDING,
        self::RIDER_ASSIGNED,
        self::WAITING_FOR_PICKUP,
    ];

    /** States where cancellation requires admin/ops approval, not self-service. */
    public const ADMIN_ONLY_CANCELLABLE = [
        self::RIDER_ARRIVED_PICKUP,
        self::FAILED_DELIVERY,
        self::DISPUTED,
    ];

    public static function isTerminal(string $status): bool
    {
        return in_array($status, self::TERMINAL, true);
    }

    public static function isValid(string $status): bool
    {
        return in_array($status, self::ALL, true);
    }
}
