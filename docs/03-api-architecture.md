# 03 — API Architecture

Base URL: `https://api.rideron.in/api/v1` (dev: `http://localhost:8000/api/v1`)
Auth: Laravel Sanctum bearer tokens. Token abilities: `customer`, `partner`, `admin`. Refresh via short-lived access token + refresh token pair issued at login (custom on top of Sanctum: `personal_access_tokens` + a `refresh_tokens` table with rotation).

## Envelope (every response)

Success:
```json
{ "success": true, "data": { }, "message": "OK" }
```
Error:
```json
{ "success": false, "message": "Human readable message", "errors": { "field": ["reason"] }, "error_code": "OTP_EXPIRED" }
```
`error_code` is a stable machine-readable string the app switches on (never parse `message`). Full list maintained in `app/Constants/ErrorCodes.php`, mirrored in `src/constants/errorCodes.ts`.

Pagination (list endpoints): `{ "success": true, "data": [...], "meta": { "page":1, "per_page":20, "total":57 } }`

## Versioning
Route group `Route::prefix('v1')`. Breaking changes get `v2` prefix; old version kept alive for a documented deprecation window. Mobile app sends `X-App-Version` header for analytics/forced-update checks.

## Endpoint list

### Auth — `/auth`
- `POST /auth/otp/request` — body `{ phone, purpose: "login" }` → sends login OTP (rate-limited: 3/hour/phone via Redis)
- `POST /auth/otp/verify` — body `{ phone, otp }` → returns `{ access_token, refresh_token, is_new_user }`
- `POST /auth/refresh` — body `{ refresh_token }` → new token pair (rotates refresh token)
- `POST /auth/logout` — revokes current token
- `POST /auth/profile` — (auth) complete profile: `{ name, email? }`
- `POST /auth/partner/login` — partner-specific (phone+OTP or phone+password, same OTP endpoints reused with `role` check)

### Cities / Stations / Routes — public read, admin write
- `GET /cities` — active cities
- `GET /cities/{id}/stations`
- `GET /routes?origin_station_id=&destination_station_id=` — resolves a route (distance, duration, cutoff, price_from)
- `GET /routes/{id}` 
- `GET /routes/{id}/schedules?date=YYYY-MM-DD` — available departure windows for that date, each with `departure_time, arrival_time, cutoff_at, seats_available, price_from`
- `GET /routes/popular` — for Home "Popular Routes"

### Pricing
- `POST /pricing/quote` — body `{ route_id, route_schedule_id, weight_slab, quantity, declared_value_paise, coupon_code? }` → server-computed `{ breakdown: [{label, amount_paise}], total_amount_paise, quote_token, quote_expires_at }`. `quote_token` is a short-lived signed token the order-creation endpoint requires, so the price can never drift between quote and payment.

### Prohibited Items
- `GET /prohibited-items` — current active list + current `version_id`

### Orders
- `POST /orders` — creates a `DRAFT`/`PAYMENT_PENDING` order. Body includes route, schedule, parcel, sender, receiver, `quote_token`, `prohibited_items_version_id`, `Idempotency-Key` header required. Returns order + a fresh Razorpay `provider_order_id`.
- `GET /orders` — customer's orders, `?status=active|completed|cancelled`
- `GET /orders/{booking_reference}` — full detail incl. status history timeline
- `POST /orders/{id}/cancel` — body `{ reason }`; backend enforces cancellation-window/refund-eligibility rules (doc 04)
- `GET /orders/{id}/receipt` — PDF/JSON receipt

### Payments
- `POST /payments/{payment_id}/verify` — body `{ razorpay_payment_id, razorpay_order_id, razorpay_signature }`. Verifies HMAC signature server-side, checks `payment_transactions` for duplicate `provider_payment_id` before processing (idempotent), then transitions order `PAYMENT_PENDING → BOOKED`, generates both OTPs.
- `POST /payments/webhook/razorpay` — server-to-server webhook (signature-verified via `X-Razorpay-Signature`), authoritative source of truth if the app never returns after payment (crash case).
- `GET /payments/{id}/status` — poll fallback for the "app crashed after payment" edge case.

### OTP (order-bound)
- `POST /orders/{id}/otp/pickup/resend`
- `POST /orders/{id}/otp/pickup/verify` — **partner-only**, body `{ otp }`
- `POST /orders/{id}/otp/delivery/resend`
- `POST /orders/{id}/otp/delivery/verify` — **partner-only**, body `{ otp }`
All four are rate-limited and count against `otp_verifications.max_attempts`; lock returns `error_code: OTP_LOCKED` with `locked_until`.

### Partner (role: partner)
- `GET /partner/assignments?date=` — today's assigned orders
- `GET /partner/assignments/{id}`
- `POST /partner/assignments/{id}/accept`
- `POST /partner/assignments/{id}/arrived-pickup`
- `POST /partner/assignments/{id}/start-transit` (auto-fires after pickup OTP verified; explicit endpoint kept for manual override)
- `POST /partner/assignments/{id}/arrived-destination`
- `GET /partner/earnings?range=`

### Notifications
- `GET /notifications` — paginated, `?unread=true`
- `POST /notifications/{id}/read`
- `POST /devices` — register FCM token `{ token, platform }`

### Support
- `POST /support/tickets` — `{ order_id?, category, description }`
- `GET /support/tickets`
- `GET /support/tickets/{id}`
- `GET /support/faq`

### Profile
- `GET /profile`
- `PATCH /profile`
- `GET /profile/saved-contacts?type=sender|receiver`
- `POST /profile/saved-contacts`
- `DELETE /profile/saved-contacts/{id}`

### Admin API (separate guard, consumed by Filament internally + optionally exposed for reporting)
Grouped under `/admin/*`, session-guarded (not part of the mobile Sanctum surface). Detailed in doc 06.

## Documentation
`l5-swagger` (OpenAPI 3) generated from PHP attributes on controllers; served at `/api/documentation` in non-production environments only.

## Idempotency
Every mutating endpoint that creates money-moving or state-changing side effects (`POST /orders`, `POST /payments/*/verify`, `POST /orders/{id}/cancel`) requires/honors an `Idempotency-Key` header. Middleware `EnsureIdempotency` stores `(key, route, response_hash)` in Redis for 24h and replays the original response for a repeat key instead of re-executing — this is what prevents duplicate bookings/payments from mobile network retries.

## Rate limiting
Laravel's `throttle` middleware backed by Redis: `auth/otp/request` 3/hour/phone + 10/hour/IP; `otp/*/verify` 5/15min per order+purpose (also enforced at the DB `max_attempts` level as defense in depth); general API 120/min/token.
