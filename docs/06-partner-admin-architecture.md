# 06 — Delivery Partner App & Admin Dashboard Architecture

## Delivery Partner App (React Native, separate app target)

### Why a separate app, not a role toggle
Different install audience (gig workers vs consumers), different store listing, different permission set (background-ish usage during a shift, phone dialer, camera for proof), and it lets us enforce data minimization structurally: the partner API surface (`/partner/*`) simply never returns customer PII the partner doesn't need, rather than relying on the UI to hide fields it received.

### Structure
Shares a `packages/` layer with the customer app in a monorepo (Yarn/NPM workspaces):
```
packages/
  design-tokens/     # colors, spacing, typography (source of truth for doc 07)
  api-client/        # typed fetch wrapper, shared error handling, auth token refresh
  types/             # shared TS types generated/mirrored from Laravel API resources
apps/
  customer/
  partner/
```
Partner-specific screens live in `apps/partner/src/screens`.

### Screens & data
- **Dashboard**: `GET /partner/assignments?date=today` (today's deliveries), earnings summary widget (`GET /partner/earnings`), one prominent "Active Delivery" card if any assignment is in `RIDER_ASSIGNED` or later, pre-`COMPLETED`.
- **Assignment Detail**: route, pickup/destination station, pickup time window, parcel type/weight (not declared value — partner doesn't need it unless flagged high-value for handling care), masked customer/receiver numbers with **call-via-proxy** (see below), Accept button (`POST .../accept`).
- **Pickup flow**: "Navigate" opens Maps app with station coordinates; "Call Customer" triggers proxy call; OTP entry screen posts to `/orders/{id}/otp/pickup/verify`; success screen shows timestamp + auto-advances to Transit.
- **Transit**: single "Mark Arrived at Destination" action; no busywork screens in between for MVP.
- **Delivery flow**: mirrors pickup — call receiver (proxy), OTP entry, success screen, "Complete Delivery" confirmation.
- **Earnings/History**: read-only list, `GET /partner/earnings`.

### Call-via-proxy (privacy)
Rather than exposing either party's real number to the other, the "Call" button hits `POST /orders/{id}/call/{target}` which either (a) returns a masked/virtual number via a telephony provider abstraction (`CallProvider` interface, real: Exotel/Knowlarity-style masking, mock: returns the real number in non-prod so dev/testing isn't blocked), or (b) in the absence of a configured provider, falls back to opening the native dialer with the number the API already masks server-side for display purposes — clearly logged as "mock/unmasked in dev" per the no-fake-functionality rule.

### What the partner API never includes
`declared_value_paise` beyond a coarse "high value — handle with care" boolean, full unmasked phone numbers, payment/pricing breakdown, other partners' identities, customer email.

## Admin Dashboard

### Technology decision
**Laravel + Filament** (Livewire-based admin panel package), same codebase/deploy as the API. Rationale: the admin surface here is almost entirely CRUD-over-relational-data (cities, stations, routes, schedules, pricing rules, coupons) plus a metrics dashboard and a few workflow actions (approve refund, assign partner, resolve ticket) — exactly Filament's sweet spot, and it ships in a fraction of the time a bespoke React admin would take, without sacrificing production quality (it's widely used in production Laravel apps). If a fully custom-branded admin becomes a requirement later, it can be swapped for a dedicated React SPA against the same `/admin/*`-guarded API without touching business logic — the Filament resources are a thin layer over the same Eloquent models/services the API uses.

### Structure
```
app/Filament/
  Resources/
    CityResource.php
    StationResource.php
    RouteResource.php
    RouteScheduleResource.php
    PricingRuleResource.php
    CouponResource.php
    OrderResource.php          # read-heavy, + manual status-override action (audited)
    DeliveryPartnerResource.php
    CustomerResource.php       # (Users where role=customer)
    SupportTicketResource.php
    RefundResource.php
    OtpVerificationLogResource.php  # read-only, for disputes
  Pages/
    Dashboard.php              # widgets below
  Widgets/
    TodaysBookingsWidget.php
    RevenueChartWidget.php
    DeliveryFunnelWidget.php   # completed/pending/cancelled/failed
    ActiveRidersWidget.php
    PopularRoutesWidget.php
    AvgDeliveryTimeWidget.php
    RefundAmountWidget.php
```

### Access control
Filament Shield (or hand-rolled policies) maps `users.role IN ('admin','ops','support')` to panel access; `ops`/`support` get scoped resource permissions (e.g. support agents can view/resolve tickets and view orders but not edit pricing or issue refunds — refund *approval* is admin-only, matching "never implement arbitrary refunds from mobile / admin controls refund eligibility").

### Order management specifics
`OrderResource` is primarily read + a small set of explicit actions (`Assign Partner`, `Force Cancel`, `Mark Disputed`, `Approve Refund`) — never a free-text status dropdown. Every action routes through the same `OrderStateMachine` the API uses (shared service layer), so admin actions produce the same `order_status_history` audit trail and can never bypass valid-transition checks. This directly satisfies "every state transition must be validated server-side" even for admin-triggered changes.

### Metrics dashboard data sources
All widgets query pre-aggregated or indexed columns (`orders.status`, `orders.booking_date`, `orders.total_amount_paise`) — no ad hoc heavy queries against `order_status_history` on every page load; a nightly aggregation job (`orders_daily_stats` table, Phase 2) is planned once volume justifies it.
