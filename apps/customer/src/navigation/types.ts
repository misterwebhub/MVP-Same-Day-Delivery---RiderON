import type { NavigatorScreenParams } from '@react-navigation/native';

/** backend/app/Http/Requests/Auth/RequestOtpRequest.php requires an OTP purpose; login is the only one the customer app uses. */
export type AuthStackParamList = {
  Splash: undefined;
  Onboarding: undefined;
  Login: undefined;
  OtpVerify: { phone: string };
  ProfileSetup: undefined;
};

export type AppTabsParamList = {
  Home: undefined;
  Orders: undefined;
  /** Center CTA — intercepted by a custom tabBarButton, never actually navigated to. See AppTabs.tsx. */
  BookParcel: undefined;
  Support: undefined;
  /** NavigatorScreenParams so Home's bell icon can deep-link straight to Profile/Notifications
   * instead of always landing on ProfileHome. */
  Profile: NavigatorScreenParams<ProfileStackParamList> | undefined;
};

/**
 * One flow, one screen at a time, matching docs/01's "progress indicator"
 * requirement — RouteSelect through Confirmation. Booking draft state
 * (selected route/schedule/parcel/sender/receiver/quote) lives in
 * BookingDraftContext (Task #26), not in route params, so the flow survives
 * back-navigation and an app kill mid-flow (per the Draft Persistence
 * requirement in docs/01).
 */
export type BookingStackParamList = {
  RouteSelect: undefined;
  ParcelDetails: undefined;
  /** Sender + receiver merged into one screen, and the price quote folded into
   * BookingSummary (formerly its own PriceBreakdown screen) — flow shortened
   * from 9 screens to 7 so a booking finishes in fewer taps. */
  ContactDetails: undefined;
  TimeSlot: undefined;
  BookingSummary: undefined;
  Payment: { orderId: number; paymentId: number };
  Confirmation: { orderId: number };
};

export type SupportStackParamList = {
  SupportHome: undefined;
  ReportIssue: { orderId?: number };
  TicketDetail: { ticketId: number };
  Faq: undefined;
};

export type ProfileStackParamList = {
  ProfileHome: undefined;
  SavedContacts: undefined;
  Notifications: undefined;
  Language: undefined;
  Legal: undefined;
};

/**
 * Root stack — sits above AppTabs so Booking/OrderDetails can cover the tab
 * bar when pushed, per docs/01's navigation architecture section. `Auth`
 * and `Loading` are only ever mounted while unauthenticated/booting (see
 * RootNavigator.tsx's conditional screen list) but live in the same
 * param list so screens don't need two separate typed navigators.
 */
export type RootStackParamList = {
  Loading: undefined;
  Auth: NavigatorScreenParams<AuthStackParamList>;
  Tabs: NavigatorScreenParams<AppTabsParamList>;
  Booking: NavigatorScreenParams<BookingStackParamList> | undefined;
  OrderDetails: { orderId: number };
};

declare global {
  // eslint-disable-next-line @typescript-eslint/no-namespace
  namespace ReactNavigation {
    interface RootParamList extends RootStackParamList {}
  }
}
