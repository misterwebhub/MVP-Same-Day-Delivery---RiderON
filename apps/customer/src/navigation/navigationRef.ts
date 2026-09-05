import { createNavigationContainerRef } from '@react-navigation/native';
import type { RootStackParamList } from './types';

/**
 * Module-level ref so code outside the React tree (the push-notification
 * tap handler in hooks/usePushNotifications.ts) can navigate without
 * threading a `navigation` prop through. Mirrors apps/partner's identical
 * file. Wired into <NavigationContainer ref={navigationRef}> in
 * RootNavigator.tsx.
 */
export const navigationRef = createNavigationContainerRef<RootStackParamList>();
