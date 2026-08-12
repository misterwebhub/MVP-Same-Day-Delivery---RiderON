# 08 — Folder Structure

## Repository layout (single repo, multiple apps)

```
rideron/
  backend/                 # Laravel API + Admin (Filament)
  apps/
    customer/               # React Native customer app
    partner/                 # React Native delivery-partner app
  packages/
    design-tokens/           # shared colors/typography/spacing (TS)
    api-client/              # shared typed API client, shared with both RN apps
    types/                   # shared TS types mirroring API resources
  docs/                     # this architecture documentation
```
Root `package.json` with Yarn/NPM workspaces wiring `apps/*` and `packages/*` together so both RN apps consume the same design tokens/API client without publishing to a registry.

## Backend (`backend/`) — Laravel 11

```
backend/
  app/
    Http/
      Controllers/Api/V1/
        AuthController.php
        CityController.php
        StationController.php
        RouteController.php
        PricingController.php
        OrderController.php
        PaymentController.php
        OtpController.php
        NotificationController.php
        SupportController.php
        ProfileController.php
        Partner/
          AssignmentController.php
          EarningsController.php
      Requests/                # FormRequest validation classes per endpoint
      Resources/                # API Resource transformers (control exact JSON shape/masking)
      Middleware/
        EnsureIdempotency.php
        MaskPhoneNumbers.php
    Filament/
      Resources/ ... (see doc 06)
      Widgets/ ...
    Models/
      User.php, CustomerProfile.php, DeliveryPartner.php, City.php, Station.php,
      Route.php, RouteSchedule.php, Order.php, OrderStatusHistory.php, Parcel.php,
      ParcelImage.php, Payment.php, PaymentTransaction.php, Refund.php,
      OtpVerification.php, OtpVerificationLog.php, Notification.php,
      SupportTicket.php, PricingRule.php, Coupon.php, ProhibitedItem.php,
      AppSetting.php, AuditLog.php
    StateMachines/
      OrderStateMachine.php
      Transitions/               # one class per transition for testability
    Services/
      PricingEngine.php          # server-side quote calculation
      PaymentGateway/
        PaymentGateway.php (interface)
        RazorpayGateway.php
        MockPaymentGateway.php
      Otp/
        OtpService.php           # generate/verify/lock/resend
      Sms/
        SmsProvider.php (interface)
        Msg91Provider.php
        MockSmsProvider.php
      Push/
        PushProvider.php (interface)
        FcmProvider.php
        MockPushProvider.php
      Call/
        CallProvider.php (interface)
        ExotelProvider.php
        MockCallProvider.php
      AssignmentService.php      # partner matching/assignment
      RefundService.php
    Events/                      # OrderBooked, PickupCompleted, DeliveryCompleted, ...
    Listeners/                   # SendPushNotification, SendSms, LogAnalyticsEvent, ...
    Jobs/
      ExpirePendingPayments.php
      ExpireWaitingTime.php
      AutoCompleteDeliveredOrders.php
      ProcessPaymentConfirmation.php
    Policies/                     # OrderPolicy, OtpPolicy, SupportTicketPolicy, ...
    Constants/
      ErrorCodes.php
      OrderStatus.php
  config/
    services.php (razorpay, fcm, msg91, exotel keys — all via env)
  database/
    migrations/
    seeders/
      CitySeeder.php, StationSeeder.php, RouteSeeder.php (Kanpur⇄Lucknow), PricingRuleSeeder.php, ProhibitedItemSeeder.php, AdminUserSeeder.php
    factories/
  routes/
    api.php  (v1 group)
    web.php  (Filament panel + webhook endpoints)
  tests/
    Feature/   # API endpoint tests
    Unit/      # state machine, pricing engine, OTP service
  storage/
  .env.example
```

## Customer app (`apps/customer/`) — React Native + TypeScript

```
apps/customer/
  src/
    components/            # generic reusable UI (Button, Card, Badge, Input, BottomSheet...)
    features/
      auth/
        screens/ (Login, OtpVerify, ProfileSetup)
        api.ts, hooks.ts, types.ts
      home/
        screens/ (Home)
        components/ (BookingCard, RecentOrders, PopularRoutes, HowItWorks)
      booking/
        screens/ (RouteSelect, ParcelDetails, SenderDetails, ReceiverDetails, TimeSlot, PriceBreakdown, BookingSummary, Payment, Confirmation)
        store/ (booking draft slice — persisted)
        api.ts, hooks.ts, types.ts
      orders/
        screens/ (OrderList, OrderDetails)
        api.ts, hooks.ts, types.ts
      support/
        screens/ (SupportHome, ReportIssue, TicketDetail, Faq)
      profile/
        screens/ (ProfileHome, SavedContacts, Notifications, Language, Legal)
    navigation/
      AuthStack.tsx, AppTabs.tsx, BookingStack.tsx, SupportStack.tsx, RootNavigator.tsx, linking.ts
    services/
      httpClient.ts          # wraps packages/api-client, adds auth header/refresh
      storage.ts              # MMKV/AsyncStorage wrapper
      push.ts                 # FCM registration/handlers
      analytics.ts
    hooks/
      useAuth.ts, useNetworkStatus.ts, useDraftPersistence.ts
    store/
      index.ts (Redux Toolkit or Zustand root store)
      slices/ (auth, booking-draft, notifications)
    theme/                    # re-exports packages/design-tokens, RN StyleSheet helpers
    constants/
      errorCodes.ts (mirrors backend), config.ts
    types/
    utils/
      formatters.ts (currency, phone masking, dates in IST), validators.ts
    assets/
      logo/, icons/, illustrations/
  App.tsx
  .env.development / .env.staging / .env.production
```

## Partner app (`apps/partner/`)
Mirrors the same top-level shape (`components/`, `features/dashboard`, `features/assignment`, `features/pickup`, `features/delivery`, `features/earnings`, `navigation/`, `services/`, `theme/` importing the same `packages/design-tokens`) — intentionally structured identically to the customer app so the same engineer(s) can move between them without relearning conventions, per "feature-based organization where appropriate."

## Environment separation
`.env.development` → all mock providers by default (`PAYMENT_DRIVER=mock`, `SMS_DRIVER=mock`, `PUSH_DRIVER=mock`, `CALL_DRIVER=mock`) so the app is fully runnable and demoable with zero external accounts. `.env.staging`/`.env.production` flip to real drivers once credentials exist. Mobile apps read a build-time `API_BASE_URL`/`ENV` via `react-native-config`; secrets (Razorpay *key id* only, never the secret) are the only credential-shaped values ever shipped client-side — the Razorpay secret, JWT signing keys, DB passwords, FCM server key, SMS/call provider keys live only in the Laravel `.env`, never in the mobile bundle.
