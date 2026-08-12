import type { ApiErrorCode, ApiErrorEnvelope } from '@rideron/types';

/**
 * Thrown for every non-2xx response. Carries the parsed error envelope from
 * ApiExceptionRenderer so screens can branch on `errorCode` (e.g. show a
 * "wrong OTP, N attempts left" message) instead of parsing raw text.
 */
export class ApiClientError extends Error {
  readonly status: number;
  readonly errorCode: ApiErrorCode | (string & {});
  readonly errors?: Record<string, string[]>;
  readonly envelope: ApiErrorEnvelope | null;

  constructor(status: number, envelope: ApiErrorEnvelope | null, fallbackMessage: string) {
    super(envelope?.message ?? fallbackMessage);
    this.name = 'ApiClientError';
    this.status = status;
    this.errorCode = envelope?.error_code ?? 'INTERNAL_ERROR';
    this.errors = envelope?.errors;
    this.envelope = envelope;
  }

  /** True for network-level failures where the request never reached the server. */
  get isNetworkError(): boolean {
    return this.status === 0;
  }
}
