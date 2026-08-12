/**
 * API envelope + error shapes — transcribed from
 * backend/app/Http/Responses/ApiResponse.php and
 * backend/app/Exceptions/ApiExceptionRenderer.php.
 */
export interface ApiSuccessEnvelope<T> {
  success: true;
  data: T;
  message: string;
}

/** Exact error_code strings from backend/app/Constants/ErrorCodes.php. */
export type ApiErrorCode =
  | 'VALIDATION_ERROR'
  | 'UNAUTHENTICATED'
  | 'FORBIDDEN'
  | 'NOT_FOUND'
  | 'RATE_LIMITED'
  | 'INTERNAL_ERROR'
  | 'INVALID_STATE_TRANSITION'
  | 'QUOTE_TOKEN_INVALID'
  | 'OTP_INVALID'
  | 'OTP_LOCKED'
  | 'OTP_EXPIRED'
  | 'OTP_RESEND_LIMIT_EXCEEDED'
  | 'OTP_RESEND_COOLDOWN'
  | 'OTP_ALREADY_VERIFIED'
  | 'REFRESH_TOKEN_INVALID'
  | 'INVALID_CREDENTIALS'
  | 'ACCOUNT_SUSPENDED'
  | 'IDEMPOTENCY_KEY_REQUIRED'
  | 'IDEMPOTENCY_CONFLICT'
  | 'SCHEDULE_NOT_AVAILABLE'
  | 'SEAT_UNAVAILABLE'
  | 'ORDER_NOT_CANCELLABLE'
  | 'PAYMENT_SIGNATURE_INVALID';

export interface ApiErrorEnvelope {
  success: false;
  message: string;
  error_code: ApiErrorCode | (string & {});
  /** Present only for 422 validation errors — Laravel's default {field: string[]} shape. */
  errors?: Record<string, string[]>;
  /** ApiException subclasses may merge arbitrary extra top-level keys via context(). */
  [extraContextKey: string]: unknown;
}

export interface PaginatedData<T> {
  items: T[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}
