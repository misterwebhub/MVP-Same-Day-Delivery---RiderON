# 07 — Design System

Derived directly from the reference logo and app mockups you provided: dark-navy "trust" backdrop, high-energy orange rider/CTA color, clean white cards. Communicates FAST / SAFE / TRUSTED as required.

## Logo
- Mascot: orange-armored rider with white helmet and backpack/parcel box (letter "R"), navy outline details, orange lightning-bolt motif suggesting speed.
- Wordmark: **"Rider"** in orange, **"ON"** in navy — bold, slightly italic/speed-lined sans-serif, tagline underneath in navy small-caps: "FAST AND SAME DAY DELIVERY".
- Usage: full lockup (mascot + wordmark) on splash/onboarding; wordmark-only in the app header/nav bar; mascot alone as app icon / loading spinner mark.
- Clear space: minimum padding = height of the "R" mascot's helmet on all sides. Never stretch, never recolor the mascot.
- Assets to produce as real files before implementation: `logo-full.svg`, `logo-wordmark.svg`, `logo-mark.svg` (mascot only), plus PNG @1x/2x/3x and app icon adaptive-icon set (Android) / all iOS sizes.

## Color palette

| Token | Hex | Usage |
|---|---|---|
| `color.primary` (Orange) | `#FF6A00` | Primary CTA buttons, active tab, links, price highlights, "ON" accent |
| `color.primaryDark` | `#E85A00` | Pressed/hover state of primary buttons |
| `color.primaryTint` | `#FFF1E6` | Icon chip backgrounds, subtle highlight surfaces |
| `color.secondary` (Navy) | `#0A1B3D` | Headers, hero/splash background, primary headings, bottom-nav active icon background |
| `color.secondaryTint` | `#13284F` | Cards on dark backgrounds, secondary hero panel |
| `color.surface` | `#FFFFFF` | Card backgrounds, sheet backgrounds |
| `color.background` | `#F5F6FA` | App screen background (light mode) |
| `color.textPrimary` | `#0F172A` | Body/heading text on light surfaces |
| `color.textSecondary` | `#6B7280` | Helper text, timestamps, placeholders |
| `color.textInverse` | `#FFFFFF` | Text on navy/orange surfaces |
| `color.border` | `#E5E7EB` | Input borders, dividers |
| `color.success` | `#16A34A` | Delivered/Completed badges, success states |
| `color.warning` | `#F59E0B` | Waiting-time countdown, pending states |
| `color.error` | `#DC2626` | Failed payment, cancelled, validation errors |
| `color.info` | `#2563EB` | Informational banners, in-transit badge |

Status badge mapping (never color-only — always paired with an icon + label per accessibility requirement):
- `BOOKED`/`RIDER_ASSIGNED` → info blue, icon: clock
- `PICKED_UP`/`IN_TRANSIT` → orange, icon: truck/train
- `ARRIVED_DESTINATION`/`WAITING_FOR_RECEIVER` → warning amber, icon: map-pin
- `DELIVERED`/`COMPLETED` → success green, icon: check-circle
- `CANCELLED`/`FAILED_DELIVERY`/`REFUNDED` → error red / neutral grey, icon: x-circle

## Typography
- Font family: **Poppins** (headings — matches the geometric, slightly-condensed weight of the wordmark) / **Inter** (body/UI text — excellent legibility at small sizes on Android, wide Devanagari-adjacent language support for future Hindi labels).
- Scale: `display` 28/36 Poppins SemiBold · `h1` 22/28 Poppins SemiBold · `h2` 18/24 Poppins Medium · `body` 15/22 Inter Regular · `bodyStrong` 15/22 Inter SemiBold · `caption` 13/18 Inter Regular · `micro` 11/14 Inter Medium (uppercase labels only, e.g. "PICKUP OTP").
- Minimum readable size: 13px anywhere in the app (per accessibility requirement).

## Spacing & sizing
- 4px base unit: `space.1=4 space.2=8 space.3=12 space.4=16 space.5=20 space.6=24 space.8=32`.
- Radius: `radius.sm=8 radius.md=12 radius.lg=16 radius.pill=999` — cards use `lg`, buttons/inputs use `md`, badges use `pill`.
- Minimum touch target: 48x48dp for all interactive elements (buttons, list rows, icon buttons).

## Core components (shared `packages/design-tokens` + a themed component library, e.g. built on top of React Native primitives — not a heavy third-party UI kit, to keep bundle size/control)
- **Button**: `primary` (orange fill, white text), `secondary` (navy outline, navy text), `ghost` (text-only, orange), `destructive` (red outline). All 48dp min height, `radius.md`, disabled state at 40% opacity + no shadow.
- **Card**: white surface, `radius.lg`, subtle shadow (`elevation:2`), 16px internal padding — used for route summary, order cards, rider info.
- **StatusBadge**: pill, colored per mapping above, icon + label, never icon-only or color-only.
- **StepProgress**: horizontal dots/segments across the booking flow header (8 steps per doc 01), completed = orange fill, current = orange outline, upcoming = grey.
- **Timeline** (order tracking): vertical line, filled orange/green circles for completed steps, hollow grey circle for pending, connecting line colored up to current step.
- **OTPDisplay**: large monospace-style digits (Poppins SemiBold, 32px, letter-spacing 8px) inside a bordered navy card, clearly labelled above ("Pickup OTP" / "Delivery OTP") — the two are never rendered inside the same card.
- **EmptyState**: centered illustration slot + `h2` message + optional CTA — used for "No orders yet", "No notifications".
- **ErrorState**: centered icon + friendly message + Retry button — never raw API error text.
- **SkeletonLoader**: shimmering grey blocks matching the shape of the content being loaded (route cards, order list rows).
- **OfflineBanner**: sticky top banner, warning amber, "You're offline. Please check your internet connection." with auto-retry indicator.

## Motion
Subtle only, per your "do not over-animate" instruction: 150–200ms ease-out for screen transitions and step-progress fills, a single celebratory (non-blocking, skippable) checkmark animation on Payment Success and Delivery Completed, skeleton shimmer for loading — nothing else animates by default.

## Dark mode
Not required for MVP given the target audience/usage pattern (short task-focused sessions), but tokens are structured (`color.*` semantic names, not raw hex in components) so a dark theme can be added later without touching component code.
