import type { NavigatorScreenParams } from '@react-navigation/native';

/** Single phone+password screen — POST /auth/partner/login, no OTP step. */
export type AuthStackParamList = {
  Login: undefined;
};

export type AppTabsParamList = {
  Rides: undefined;
  Profile: undefined;
};

/**
 * Root stack — sits above AppTabs so AssignmentDetail can cover the tab bar
 * when pushed, mirroring apps/customer's RootNavigator shape. `Auth` and
 * `Loading` are only ever mounted while unauthenticated/booting (see
 * RootNavigator.tsx's conditional screen list).
 */
export type RootStackParamList = {
  Loading: undefined;
  Auth: NavigatorScreenParams<AuthStackParamList>;
  Tabs: NavigatorScreenParams<AppTabsParamList>;
  AssignmentDetail: { orderId: number };
};

declare global {
  // eslint-disable-next-line @typescript-eslint/no-namespace
  namespace ReactNavigation {
    interface RootParamList extends RootStackParamList {}
  }
}
