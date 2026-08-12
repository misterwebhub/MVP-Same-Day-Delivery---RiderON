# 05 — Payment & OTP Architecture

## Payment architecture

### Provider abstraction
```php
interface PaymentGateway {
    public function createOrder(int $amountPaise, string $currency, string $receipt): PaymentGatewayOrder;
    public function verifySignature(array $payload): bool;
    public function fetchPayment(string $providerPaymentId): PaymentGatewayPayment;
    public function refund(string $providerPaymentId, int $amountPaise): PaymentGatewayRefund;
}
```
Bindings: `RazorpayGateway` (real, `laravel/config('services.razorpay')`), `MockPaymentGateway` (dev/test — simulates success/failure/timeout via a query param or fixed test card numbers, logs instead of calling out). Selected via `PAYMENT_DRIVER` env, bound in `PaymentServiceProvider`. Controllers/services depend only on the `PaymentGateway` interface — swapping to Stripe later means writing `StripeGateway`, zero controller changes.

### Flow
1. Client calls `POST /orders` with a valid `quote_token` (from `/pricing/quote`, signed, short TTL ~10 min) + `Idempotency-Key`.
2. Server re-validates the quote token server-side (never trusts client-sent amount), creates `orders` row (`PAYMENT_PENDING`) + `payments` row, calls `PaymentGateway::createOrder()` → Razorpay order id.
3. Client opens Razorpay Checkout SDK with `provider_order_id`, key, amount, prefill.
4. On checkout completion (success OR the app resuming after a crash), client calls `POST /payments/{id}/verify` with the returned `razorpay_payment_id/order_id/signature`.
5. Server: checks `payment_transactions` for an existing row with that `provider_payment_id` (duplicate-callback guard) → if present and already `processed`, returns the existing result idempotently. Otherwise verifies HMAC signature server-side using the webhook secret; on success, records the transaction, marks `payments.status = success`, and drives `OrderStateMachine::confirmPayment()` (`PAYMENT_PENDING → BOOKED`), generating both OTPs.
6. **Webhook** (`POST /payments/webhook/razorpay`) is the source of truth of last resort: if the client never calls `/verify` (app killed, network dropped), the webhook independently verifies and processes the same transition. Both paths converge on the same idempotent service method (`ProcessPaymentConfirmation`), safe to call twice.
7. Client fallback: if `/verify` times out or the app force-closed and reopened, `GET /orders/{id}` or `GET /payments/{id}/status` tells the app the true current state — the UI never assumes success from its own memory.

### Failure states handled explicitly
- **Payment failed** → `PAYMENT_FAILED`, UI: "Payment couldn't be completed" + Retry Payment / Change Method.
- **Cancelled by user in checkout** → same `PAYMENT_FAILED` path, distinct `failure_reason`.
- **Timeout** (no callback within N minutes) → scheduled job flips stale `PAYMENT_PENDING` → `PAYMENT_FAILED`, releasing any held schedule capacity.
- **Duplicate callback** (double-tap, webhook + client both fire) → unique constraint on `payment_transactions(provider_payment_id, event_type)` + idempotent processor.
- **Payment succeeds, booking creation logic throws** → payment confirmation and booking finalization happen in the *same* DB transaction as part of `ProcessPaymentConfirmation`; if anything after "mark payment success" fails, the whole transaction rolls back and the job is retried (queued, not inline) until it succeeds — money is never captured with no corresponding confirmed order. If retries are exhausted, it's escalated to a `DISPUTED`-adjacent `payment_reconciliation_required` admin alert rather than silently losing the payment.

## OTP architecture

### Generation
- Both Pickup OTP and Delivery OTP are generated server-side the moment `BOOKED` is reached (so the customer sees them immediately on the confirmation screen — no need to "request" them).
- 4-digit numeric, `random_int(1000, 9999)`, cryptographically fine for this threat model (short-lived, single order, rate-limited, not password-grade).
- Stored as `otp_hash` = `Hash::make($otp)` (bcrypt) — plaintext exists only in memory for the single request/notification that issues it, never persisted, never written to logs (custom log context scrubber strips any key literally named `otp`).
- `expires_at`: pickup OTP expires at route cutoff/journey-start + buffer; delivery OTP expires at `waiting_deadline_at` + buffer (configurable). Expired OTPs are resend-able, not silently dead ends.

### Verification (partner-side)
- `POST /orders/{id}/otp/pickup/verify` and the delivery equivalent — **only partner role, only the assigned partner for that order** (policy check).
- Compares `Hash::check($input, $otp_hash)`.
- On mismatch: increment `attempt_count`, log an `otp_verification_logs` row (`result: invalid`, no OTP value stored), return `error_code: OTP_INVALID` with attempts-remaining count.
- On `attempt_count >= max_attempts` (default 5): set `locked_until = now + 15min`, return `error_code: OTP_LOCKED`. While locked, verify requests short-circuit without touching the hash (prevents brute force even against a fresh guess) and get an `OTP_LOCKED` log entry.
- Redis mirrors the lock/attempt counters (`otp:lock:{order_id}:{purpose}`) for fast checks under load; MySQL remains the durable source of truth reconciled on each check.
- On success: `verified_at` set, order transition fires (`PICKED_UP` or `DELIVERED`), log `result: success`.

### Resend
- `POST /orders/{id}/otp/{purpose}/resend` — capped `resend_count` (default 3 per order/purpose), min interval between resends (e.g. 60s) enforced via Redis, generates a fresh OTP + hash + expiry, invalidates the previous one.

### UI separation (customer app)
- Confirmation screen renders two visually distinct cards: **"Pickup OTP"** (labelled, used *by you* at origin) and **"Delivery OTP"** (labelled, used *by the receiver* at destination) — never merged into one shared "your OTPs" block, and copy always states who uses which and where, per your requirement to avoid confusion. Receiver's OTP is also delivered directly to the receiver's phone via SMS (since the receiver may not have the app), independent of what the customer sees.

### What partner never sees
- The partner app only ever exposes an OTP **input field**, never the correct value anywhere in its API responses — `GET /partner/assignments/{id}` deliberately omits any OTP field from its payload.
