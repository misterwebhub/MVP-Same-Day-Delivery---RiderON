# 04 — Order State Machine

Implemented server-side as `App\StateMachines\OrderStateMachine` — a transition table, not scattered `if` statements. Every transition is a method that (a) checks current state is a valid "from", (b) checks the actor is authorized for that transition, (c) performs side effects, (d) writes `order_status_history`, (e) fires an event (→ notification listeners), all inside one DB transaction with `SELECT ... FOR UPDATE` row locking on the order to prevent two actors racing the same transition (e.g. two OTP verify requests at once).

## States

| State | Meaning |
|---|---|
| `DRAFT` | Order object created client-side context only (not persisted, or persisted transiently) before payment starts |
| `PAYMENT_PENDING` | Order row exists, Razorpay order created, awaiting payment |
| `PAYMENT_FAILED` | Payment attempt failed/cancelled/timed out — order can retry payment or expire |
| `BOOKED` | Payment verified, both OTPs generated. Waiting for rider assignment |
| `RIDER_ASSIGNMENT_PENDING` | Explicit state if assignment isn't instant (manual/ops assignment) |
| `RIDER_ASSIGNED` | Partner accepted the assignment |
| `WAITING_FOR_PICKUP` | Customer notified, waiting at/heading to station |
| `RIDER_ARRIVED_PICKUP` | Partner marked arrived at pickup station |
| `PICKUP_OTP_PENDING` | Partner opened OTP entry screen (UI-only convenience state, optional to persist) |
| `PICKED_UP` | Pickup OTP verified |
| `IN_TRANSIT` | Journey started |
| `ARRIVED_DESTINATION` | Partner marked arrived at destination station |
| `WAITING_FOR_RECEIVER` | Waiting-time clock running (`waiting_deadline_at` set) |
| `DELIVERY_OTP_PENDING` | Partner opened delivery OTP entry (UI convenience) |
| `DELIVERED` | Delivery OTP verified |
| `COMPLETED` | Terminal success — auto-set shortly after `DELIVERED` (config: immediate or +N min grace for disputes) |
| `CANCELLED` | Terminal — cancelled by customer/admin/system before completion |
| `REFUND_PENDING` | Refund requested/owed, awaiting processing |
| `REFUNDED` | Terminal — refund completed |
| `FAILED_DELIVERY` | Waiting time expired and no resolution reached (return-to-origin / reschedule path) |
| `DISPUTED` | Manual hold state, e.g. damaged parcel claim, frozen pending support/admin action |

## Transition table

| From | To | Trigger | Actor |
|---|---|---|---|
| — | `PAYMENT_PENDING` | `POST /orders` | customer |
| `PAYMENT_PENDING` | `PAYMENT_FAILED` | payment callback/webhook = failed, or quote/payment timeout (queued job) | system |
| `PAYMENT_FAILED` | `PAYMENT_PENDING` | retry payment (new Razorpay order, same booking draft) | customer |
| `PAYMENT_PENDING` | `BOOKED` | payment signature verified (webhook is authoritative; app callback is a hint) | system |
| `BOOKED` | `RIDER_ASSIGNMENT_PENDING` | immediately after booking if auto-assignment queue hasn't matched yet | system |
| `RIDER_ASSIGNMENT_PENDING` | `RIDER_ASSIGNED` | assignment algorithm/admin manual assign + partner acceptance | system/admin, confirmed by partner |
| `RIDER_ASSIGNED` | `WAITING_FOR_PICKUP` | auto, once assigned | system |
| `WAITING_FOR_PICKUP` | `RIDER_ARRIVED_PICKUP` | `POST /partner/assignments/{id}/arrived-pickup` | partner |
| `RIDER_ARRIVED_PICKUP` | `PICKED_UP` | `POST /orders/{id}/otp/pickup/verify` success | partner |
| `PICKED_UP` | `IN_TRANSIT` | auto on pickup OTP success (or explicit start-transit call) | system/partner |
| `IN_TRANSIT` | `ARRIVED_DESTINATION` | `POST /partner/assignments/{id}/arrived-destination` | partner |
| `ARRIVED_DESTINATION` | `WAITING_FOR_RECEIVER` | auto, sets `waiting_deadline_at = now + route.waiting_time_minutes` | system |
| `WAITING_FOR_RECEIVER` | `DELIVERED` | `POST /orders/{id}/otp/delivery/verify` success | partner |
| `WAITING_FOR_RECEIVER` | `FAILED_DELIVERY` | `waiting_deadline_at` passed with no successful OTP (scheduled job) | system |
| `FAILED_DELIVERY` | `WAITING_FOR_RECEIVER` | admin extends waiting time / reschedules meeting | admin |
| `FAILED_DELIVERY` | `REFUND_PENDING` / `CANCELLED` | admin resolves per configured policy | admin |
| `DELIVERED` | `COMPLETED` | auto after grace period (app_settings: `order.auto_complete_minutes`) or immediately if configured 0 | system |
| any pre-`PICKED_UP` state | `CANCELLED` | `POST /orders/{id}/cancel` (eligibility rule per state, see below) | customer/admin |
| `PICKED_UP`…`WAITING_FOR_RECEIVER` | `DISPUTED` | support escalation (damaged/lost/wrong parcel report) | admin |
| `DISPUTED` | `REFUND_PENDING` / `COMPLETED` / `CANCELLED` | admin resolution | admin |
| `CANCELLED`/`REFUND_PENDING` | `REFUNDED` | refund processed via Razorpay refund API | system (admin-approved) |

## Cancellation eligibility (admin-configurable via `app_settings`, defaults shown)

| Stage | Eligible to cancel? | Refund |
|---|---|---|
| `PAYMENT_PENDING` / `PAYMENT_FAILED` | Yes, always (no charge captured or auto-void) | N/A |
| `BOOKED` (before assignment) | Yes | 100% |
| `RIDER_ASSIGNED` / `WAITING_FOR_PICKUP` | Yes, with cutoff (e.g. up to 30 min before scheduled departure) | 100% minus cancellation fee if within cutoff window (configurable %, default 0) |
| `RIDER_ARRIVED_PICKUP` onward, pre-`PICKED_UP` | Admin-only cancel (customer can *request*, ops approves) | Partial, admin-set |
| `PICKED_UP` and beyond | Not self-service; becomes a support ticket / `DISPUTED` flow | Per admin policy |

Every rule above lives in `app_settings` (`cancellation.*` keys) — nothing is a hard-coded constant in controllers.

## Concurrency & idempotency safeguards
- Row-level lock (`lockForUpdate`) on `orders` during any transition.
- Transition methods assert `current_status IN (allowed_from_states)`; a second concurrent request that already lost the race gets `error_code: INVALID_STATE_TRANSITION` cleanly instead of corrupting state.
- OTP verify endpoints are additionally guarded by the `otp_verifications` row's own lock + attempt counter, independent of order-level locking.
- Scheduled jobs (`payment timeout`, `waiting-time expiry`, `auto-complete`) use `withoutOverlapping` + are naturally idempotent because they check current state before acting.
