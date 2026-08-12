<?php

namespace App\Constants;

/**
 * Central, stable registry of machine-readable `error_code` values returned
 * in the API error envelope (docs/03-api-architecture.md). Mirrored in the
 * mobile app's src/constants/errorCodes.ts. Clients switch on these values —
 * never on the human-readable `message`.
 */
final class ErrorCodes
{
    // Generic / framework-level
    public const VALIDATION_ERROR = 'VALIDATION_ERROR';

    public const UNAUTHENTICATED = 'UNAUTHENTICATED';

    public const FORBIDDEN = 'FORBIDDEN';

    public const NOT_FOUND = 'NOT_FOUND';

    public const RATE_LIMITED = 'RATE_LIMITED';

    public const INTERNAL_ERROR = 'INTERNAL_ERROR';

    // Orders / state machine
    public const INVALID_STATE_TRANSITION = 'INVALID_STATE_TRANSITION';

    // Pricing
    public const QUOTE_TOKEN_INVALID = 'QUOTE_TOKEN_INVALID';

    // OTP
    public const OTP_INVALID = 'OTP_INVALID';

    public const OTP_LOCKED = 'OTP_LOCKED';

    public const OTP_EXPIRED = 'OTP_EXPIRED';

    public const OTP_RESEND_LIMIT_EXCEEDED = 'OTP_RESEND_LIMIT_EXCEEDED';

    public const OTP_RESEND_COOLDOWN = 'OTP_RESEND_COOLDOWN';

    public const OTP_ALREADY_VERIFIED = 'OTP_ALREADY_VERIFIED';

    // Auth
    public const REFRESH_TOKEN_INVALID = 'REFRESH_TOKEN_INVALID';

    public const INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';

    public const ACCOUNT_SUSPENDED = 'ACCOUNT_SUSPENDED';

    // Idempotency
    public const IDEMPOTENCY_KEY_REQUIRED = 'IDEMPOTENCY_KEY_REQUIRED';

    public const IDEMPOTENCY_CONFLICT = 'IDEMPOTENCY_CONFLICT';

    // Orders / Payments
    public const SCHEDULE_NOT_AVAILABLE = 'SCHEDULE_NOT_AVAILABLE';

    public const SEAT_UNAVAILABLE = 'SEAT_UNAVAILABLE';

    public const ORDER_NOT_CANCELLABLE = 'ORDER_NOT_CANCELLABLE';

    public const PAYMENT_SIGNATURE_INVALID = 'PAYMENT_SIGNATURE_INVALID';
}
