# 01 — Product Architecture

## Systems

```
┌─────────────────────┐     ┌─────────────────────┐     ┌─────────────────────┐
│  Customer App (RN)   │     │ Partner App (RN)     │     │ Admin Dashboard      │
│  iOS + Android       │     │ iOS + Android        │     │ Laravel/Filament web │
└──────────┬───────────┘     └──────────┬───────────┘     └──────────┬───────────┘
           │                            │                            │
           └──────────────┬─────────────┴──────────────┬─────────────┘
                           │        HTTPS / JSON         │
                  ┌────────▼─────────────────────────────▼────────┐
                  │           Laravel API (api/v1/*)               │
                  │  Auth · Orders · Pricing · OTP · Payments ·    │
                  │  Notifications · Support · Routes · Admin API  │
                  └───┬───────────┬───────────┬───────────┬────────┘
                      │           │           │           │
                 ┌────▼───┐  ┌────▼───┐  ┌────▼────┐ ┌────▼─────┐
                 │ MySQL  │  │ Redis  │  │ S3-comp. │ │ Queue     │
                 │        │  │(OTP/   │  │ storage  │ │ workers   │
                 │        │  │ cache/ │  │ (parcel  │ │ (notifs,  │
                 │        │  │ locks) │  │ photos)  │ │  SMS)     │
                 └────────┘  └────────┘  └──────────┘ └───┬───────┘
                                                            │
                                       ┌────────────────────┼───────────────────┐
                                  ┌─────▼─────┐      ┌───────▼──────┐    ┌───────▼──────┐
                                  │ Razorpay  │      │ FCM (push)   │    │ SMS/WhatsApp │
                                  │ (real/mock)│     │ (real/mock)  │    │ (real/mock)  │
                                  └───────────┘      └──────────────┘    └──────────────┘
```

Every external integration (Razorpay, FCM, SMS, WhatsApp, Maps) sits behind a Laravel interface + service container binding, switched by `.env` (`PAYMENT_DRIVER=razorpay|mock`, `SMS_DRIVER=msg91|mock`, etc.). Mock drivers log to `storage/logs` and (in dev) return deterministic OTPs/fixtures instead of pretending to call a real vendor.

## User types & roles

| Role | Auth | App |
|---|---|---|
| `customer` | Mobile OTP login (Sanctum token) | Customer RN app |
| `partner` | Mobile+password or Mobile OTP login (Sanctum token, `partner` ability) | Partner RN app |
| `admin` / `ops` / `support` | Email+password (session, Filament) | Admin web |

Role is enforced server-side via Sanctum token abilities + Laravel policies — never trusted from the client.

## End-to-end user journey (customer)

1. **Discover** — Splash → Onboarding (3 screens) → Login (mobile number) → OTP verify → Profile (name, optional email).
2. **Book**
   - Home → "Send a parcel today" card → pick From city/station, To city/station → Continue
   - Route Selection screen shows the resolved route card (distance, duration, price-from, cutoff) pulled from `/routes`
   - Parcel Details → Sender Details → Receiver Details → Delivery Time Slot (from `/routes/{id}/schedules`)
   - Price Breakdown (server-quoted) → Booking Summary + prohibited-items declaration
   - Payment (Razorpay checkout) → server verifies signature → Booking Confirmed screen (Booking ID, Pickup OTP, Delivery OTP shown separately)
3. **Pickup** — Order Tracking screen shows "Rider assigned" → rider card (name, photo, rating, masked phone) → customer calls/messages → hands parcel → gives Pickup OTP → partner verifies → status flips to `PICKED_UP`, customer sees confirmation + timestamp.
4. **Transit** — In-Transit screen: route diagram, picked-up-at time, expected arrival, current status. No live GPS in MVP (architecture allows adding it later via a `partner_locations` ping table + websocket/polling endpoint).
5. **Destination** — Receiver gets push+SMS "parcel arrived" with partner contact + meeting point + waiting deadline.
6. **Delivery** — Receiver meets partner, gives Delivery OTP, partner verifies → `DELIVERED` → customer gets confirmation → order auto-transitions to `COMPLETED` after a short grace window (configurable) or immediately, per admin setting.
7. **Post-delivery** — Order appears in History (Completed tab), receipt available, support/report-issue available for N days.

Parallel/branch journeys: cancellation (multiple points), waiting-time-expired workflow, payment failure/retry, refund request — all detailed in [04-state-machine.md](04-state-machine.md) and [09-roadmap-and-testing.md](09-roadmap-and-testing.md) edge-case matrix.

## Delivery Partner journey

Login → Dashboard (today's assignments, earnings summary) → Accept assignment → Navigate-to-pickup (deep link to Maps) → Call customer → Enter Pickup OTP → journey starts (`IN_TRANSIT`) → Mark arrived at destination → Call receiver → Enter Delivery OTP → Complete.

Partner never sees: customer's unmasked phone number outside the call-via-proxy action, parcel declared value beyond what's operationally necessary, payment details, other partners' data.

## Admin journey

Ops team logs in → Dashboard metrics → manages Cities/Stations/Routes/Schedules/Pricing rules → monitors Orders in real time → handles Support Tickets/Refunds → reviews OTP verification logs (for disputes) → manages Partners (onboarding, verification badge, active status).

## Full screen map

### Customer App
```
Splash
Onboarding (1/2/3)
Auth
 ├─ Login (Mobile Number)
 ├─ OTP Verification
 └─ Basic Profile Setup
Home (Tab)
 ├─ Booking Card (From/To/Date/Delivery)
 ├─ Active Delivery banner
 ├─ Recent Orders list
 ├─ How It Works
 ├─ Popular Routes
 └─ Support shortcut
Booking Flow (stack, one flow, progress indicator)
 ├─ Select From City → Select From Station
 ├─ Select To City → Select To Station
 ├─ Route Summary (distance/time/cutoff/price-from)
 ├─ Parcel Details (type, weight, qty, value, photo, instructions, declaration)
 ├─ Sender Details
 ├─ Receiver Details
 ├─ Delivery Time Slot picker
 ├─ Price Breakdown
 ├─ Booking Summary
 └─ Payment (Razorpay checkout) → Payment Result (success/fail/pending)
Booking Confirmation (Booking ID + Pickup OTP + Delivery OTP, "what happens next")
Order Tracking / Details (single screen, state-driven content + vertical timeline)
 ├─ Rider Assigned card (call/message/instructions)
 ├─ Pickup pending card
 ├─ In-Transit card
 ├─ Arrived-at-Destination card (receiver view content)
 └─ Delivered / Completed card (receipt)
Orders (Tab) — Active / Completed / Cancelled tabs, Order card → Order Tracking
Support (Tab)
 ├─ Support Home (Call/Chat/FAQ/Report Issue)
 ├─ Report an Issue (category picker + order picker + description)
 ├─ Ticket Detail
 └─ FAQ list/detail
Profile (Tab)
 ├─ Profile Home (name/phone/email, menu)
 ├─ Saved Sender/Receiver addresses
 ├─ Payment history
 ├─ Notifications list
 ├─ Language (English/Hindi)
 ├─ Terms / Privacy / Prohibited Items
 └─ Logout
Shared / cross-cutting
 ├─ Network Offline screen/banner
 ├─ Generic Error state
 └─ Generic Empty state
```

### Delivery Partner App
```
Splash → Login → OTP Verification
Dashboard (Today's deliveries, earnings, active delivery)
Assignment Detail (accept/decline, route, pickup time, contacts)
Pickup Flow (navigate, call customer, enter Pickup OTP)
Transit (mark arrived at destination)
Delivery Flow (call receiver, enter Delivery OTP, complete)
Earnings / History
Profile
```

### Admin Dashboard (web)
```
Login
Dashboard (metrics + charts)
Customers (list/detail)
Delivery Partners (list/detail/verification)
Cities / Stations / Routes / Schedules (CRUD)
Pricing Rules (CRUD)
Coupons (CRUD) — Phase 2
Orders (list/detail, manual status override with audit log)
Payments / Refunds
OTP Verification Logs
Support Tickets
Notifications (broadcast/templates)
Settings (waiting-time policy, cutoff defaults, prohibited items list)
```

## Navigation architecture (React Native)

- Root: `NavigationContainer`
  - `AuthStack` (unauthenticated): Splash → Onboarding → Login → OTPVerify → ProfileSetup
  - `AppTabs` (authenticated, bottom tabs): Home, Orders, BookParcel (center CTA), Support, Profile — mirrors the reference screenshot's tab bar
  - `BookingStack` (modal/stack pushed from Home or Book tab): RouteSelect → ParcelDetails → SenderDetails → ReceiverDetails → TimeSlot → PriceBreakdown → BookingSummary → Payment → Confirmation
  - `OrderDetailsScreen` reachable from Home/Orders/Confirmation/deep link (`riderona://orders/{id}`)
  - `SupportStack`: SupportHome → ReportIssue → TicketDetail
- State: booking-in-progress draft persisted to MMKV/AsyncStorage keyed by draft id, restored if app is killed mid-flow (per Draft Persistence requirement).
- Deep links: order tracking, payment return URL, notification tap → order details.
