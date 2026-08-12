# RiderON — Architecture Documentation

Brand: **RiderON** — "Fast and Same Day Delivery"
Model: Intercity station-to-station parcel delivery, launching with **Kanpur Central (CNB) ⇄ Lucknow Charbagh (LKO)**.

Stack decision (per your instructions):
- Backend: **Laravel 11** (PHP), REST API, MySQL 8
- Mobile: **React Native + TypeScript** (customer app first; partner app is a second app target sharing the same design system/services pattern)
- Admin: Laravel-based web dashboard (Blade/Livewire or a lightweight React admin — decided in doc 06)
- Cache/Queue/OTP-throttling: Redis
- Payments: Razorpay (real provider) with a Mock provider behind an interface
- Notifications: Firebase Cloud Messaging (real) with a Mock/log provider
- SMS/WhatsApp: provider-abstracted (mock by default in dev)
- Storage: S3-compatible (local disk driver in dev)

This directory is the single source of truth for architecture. Read in order:

| Doc | Contents |
|---|---|
| [01-product-architecture.md](01-product-architecture.md) | User types, end-to-end journeys, full screen map, navigation architecture |
| [02-database-schema.md](02-database-schema.md) | MySQL ERD — every table, column, key, index |
| [03-api-architecture.md](03-api-architecture.md) | REST endpoint list, request/response envelope, versioning, auth |
| [04-state-machine.md](04-state-machine.md) | Order status state machine, valid transitions, who can trigger what |
| [05-payment-otp-architecture.md](05-payment-otp-architecture.md) | Razorpay flow, idempotency, OTP generation/verification/security |
| [06-partner-admin-architecture.md](06-partner-admin-architecture.md) | Delivery Partner app architecture + Admin dashboard architecture |
| [07-design-system.md](07-design-system.md) | Color theme (from your reference images), typography, components, logo usage |
| [08-folder-structure.md](08-folder-structure.md) | Laravel repo layout + React Native repo layout |
| [09-roadmap-and-testing.md](09-roadmap-and-testing.md) | Phase 1/2/3 scope, test strategy, edge-case matrix |

## Status
Phase 0 (this doc set) — **complete, pending your confirmation**.
Phase 1 (MVP implementation) — **not started**. Waiting for go-ahead per your "wait for confirmation before implementing" instruction.

## Key decisions worth flagging before we build
1. **MySQL confirmed** per your latest message (overrides the earlier Postgres/MySQL either-or).
2. **Laravel Sanctum** (not Passport) for API token auth — simpler, first-party, fits a mobile-token use case without full OAuth2 overhead. Flag if you specifically want OAuth2/Passport.
3. Admin dashboard: recommending **Laravel + Livewire/Filament** instead of a separate React SPA — ships faster, stays inside one codebase/one deploy, still fully production-grade. Filament in particular gives us resource CRUD (cities/stations/routes/pricing/coupons) almost for free, which matters a lot given the size of this backend. Can swap for a React admin later without touching the API.
4. Delivery Partner app: a **second React Native app** (not a role toggle inside the customer app) sharing a common `packages/` (design tokens, API client, types) via a monorepo — cleaner permissions/store listing separation, matches your "partner must never see sensitive info unnecessarily" requirement structurally rather than by UI hiding.
5. Two OTPs are modeled as two rows in one `otp_verifications` table with a `purpose` enum (`pickup`, `delivery`), not two tables — keeps verification/lockout/audit logic in one place while the UI still treats them as fully distinct concepts.
