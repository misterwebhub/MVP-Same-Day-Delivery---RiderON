import { createNavigationContainerRef } from '@react-navigation/native';
import type { RootStackParamList } from './types';

/**
 * Module-level ref so code outside the React tree (the push-notification
 * tap handler in hooks/usePushNotifications.ts) can navigate without
 * threading a `navigation` prop through. Standard React Navigation pattern
 * for "navigate from anywhere" — see RootNavigator.tsx where this is wired
 * into <NavigationContainer ref={navigationRef}>.
 */
export const navigationRef = createNavigationContainerRef<RootStackParamList>();
