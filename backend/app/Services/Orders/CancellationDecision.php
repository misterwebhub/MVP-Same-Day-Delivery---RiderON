<?php

namespace App\Services\Orders;

/**
 * Result of evaluating an order against docs/04-state-machine.md's
 * cancellation eligibility table.
 */
final class CancellationDecision
{
    private function __construct(
        public readonly bool $cancellable,
        public readonly bool $adminOnly,
        public readonly int $refundPercentage,
        public readonly bool $requiresRefund,
    ) {
    }

    public static function cancellable(int $refundPercentage, bool $requiresRefund): self
    {
        return new self(true, false, $refundPercentage, $requiresRefund);
    }

    public static function adminOnly(): self
    {
        return new self(false, true, 0, false);
    }

    public static function notCancellable(): self
    {
        return new self(false, false, 0, false);
    }
}
