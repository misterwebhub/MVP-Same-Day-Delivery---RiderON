# 09 — Development Roadmap & Testing Strategy

## Phase 1 — MVP (build order, each step demoable)

1. **Backend foundation**: Laravel project, MySQL migrations for all core tables (doc 02, minus Phase-2-only ones), seeders for Kanpur Central/Lucknow Charbagh cities/stations/route (both directions)/schedules/pricing rules/prohibited items/an admin user, Sanctum auth scaffolding, response envelope + error-code middleware, mock providers for payment/SMS/push/call wired by default.
2. **Auth + Cities/Stations/Routes/Pricing read APIs** — customer app can log in and see a real, backend-driven Kanpur⇄Lucknow route with real price-from.
3. **Order creation + Pricing quote engine** (server-side, no payment yet) — booking flow up to Booking Summary works end to end against real data.
4. **Payment integration** (mock provider first, Razorpay wiring behind the same interface) — full payment → BOOKED → OTP generation flow, idempotency + webhook handling.
5. **OTP verify endpoints + state machine transitions through PICKED_UP → IN_TRANSIT → ARRIVED_DESTINATION → DELIVERED → COMPLETED.**
6. **Partner app MVP**: login, assignment list, accept, pickup OTP, mark arrived, delivery OTP, complete.
7. **Customer order tracking/history + notifications (mock push, real persistence) + support ticket creation.**
8. **Admin (Filament)**: Orders, Customers, Partners, Stations/Routes/Schedules, Pricing Rules, Payments/Refunds, basic dashboard widgets.
9. **Hardening pass**: rate limiting, idempotency edge cases, error-code coverage, accessibility pass, offline/retry behavior in the RN apps.

Explicitly **out of scope for Phase 1**: live GPS tracking, coupons/wallet, ratings/chat, automated (non-basic) push templates beyond the event list in doc 01, multi-city expansion UI (backend entities already support it, just not exposed in city picker beyond Kanpur/Lucknow).

## Phase 2

Live partner location pings + map view, advanced partner management (multi-partner-per-route load balancing, leave/availability calendar), coupons, wallet/credit, ratings & reviews, in-app chat (customer↔partner, customer↔support), templated/automated notification campaigns, richer analytics dashboards (`orders_daily_stats` aggregation job), waiting-time policy automation (auto-apply fee, auto-trigger return-to-origin).

## Phase 3

Multi-city route expansion (Delhi↔Agra etc. — pure data/config addition given the architecture), door-to-door delivery mode (adds address-based pickup/drop alongside station-based, new `delivery_mode` on routes/orders), multiple logistics partner integration (marketplace of partner orgs, not just individual riders), AI modules per doc's AI-features list (support assistant, prohibited-item risk scoring, demand/pricing recommendations, ticket auto-classification) — each designed as an isolated service behind a feature flag so it can be evaluated without destabilizing core booking/delivery flows.

## Testing strategy

### Backend (PHPUnit/Pest)
- **Unit**: `OrderStateMachine` (every valid transition + every invalid transition rejected), `PricingEngine` (weight slabs, peak hour, coupon stacking), `OtpService` (hash/verify/lock/resend/expiry).
- **Feature (API)**: full booking→payment→OTP→delivery happy path; auth OTP request/verify; route/pricing read endpoints; cancellation eligibility per state; refund workflow; support ticket creation.
- **Critical edge cases** (each a named test):
  - Wrong OTP → `attempt_count` increments, correct `error_code`.
  - Expired OTP → `error_code: OTP_EXPIRED`, resend works.
  - 5 consecutive wrong OTPs → `OTP_LOCKED`, further attempts short-circuit without touching hash.
  - Duplicate Razorpay webhook for same `provider_payment_id` → processed exactly once (assert `payment_transactions` unique constraint + no double state transition).
  - Order creation with reused `Idempotency-Key` → same response replayed, no duplicate order row.
  - Payment succeeds but downstream confirmation job fails mid-transaction → transaction rolls back, order stays `PAYMENT_PENDING`, retry job eventually reconciles (or escalates after max retries).
  - Network failure simulated during order creation (client retries with same idempotency key) → single order.
  - Receiver never arrives → scheduled job flips `WAITING_FOR_RECEIVER → FAILED_DELIVERY` at `waiting_deadline_at`.
  - Rider unavailable / assignment fails → order stays `RIDER_ASSIGNMENT_PENDING`, visible to admin for manual assignment, customer sees honest "finding a delivery partner" state (not a fake "assigned").
  - Two concurrent requests to verify the same OTP → row lock ensures only one succeeds, second gets a clean `INVALID_STATE_TRANSITION`/already-verified response, not a race corruption.
  - Cancellation attempted after `PICKED_UP` → rejected with guidance to open a support ticket instead.
  - Authorization: partner A cannot verify OTP for an order assigned to partner B; customer cannot fetch another customer's order by guessing `booking_reference`.

### Mobile (Jest + React Native Testing Library, Detox for E2E on the critical path)
- Unit tests for pricing display formatting, phone masking, form validators (sender/receiver/parcel forms), draft persistence (kill app mid-booking, confirm restore).
- Integration tests for the booking flow reducer/store (step navigation preserves data going back/forward).
- E2E (Detox) happy path: login → book Kanpur→Lucknow parcel → mock payment → see both OTPs → (partner app) verify pickup OTP → see in-transit → verify delivery OTP → completed, run in CI against the mock-provider backend.

### CI
GitHub Actions (or equivalent): backend job runs migrations against a throwaway MySQL service container + PHPUnit/Pest + static analysis (PHPStan/Larastan); mobile job runs `tsc --noEmit`, ESLint, Jest. Both required to pass before merge — CI/CD-ready structure per your production-readiness requirement, actual deploy pipeline (staging/production) to be defined once hosting target is chosen.
