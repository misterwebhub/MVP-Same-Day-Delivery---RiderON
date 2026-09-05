import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { AuthStack } from './AuthStack';
import { AppTabs } from './AppTabs';
import { AssignmentDetail } from '../features/assignments/screens/AssignmentDetail';
import { BootSplash } from '../components/BootSplash';
import { useAuth } from '../hooks/useAuth';
import { usePushNotifications } from '../hooks/usePushNotifications';
import { navigationRef } from './navigationRef';
import type { RootStackParamList } from './types';

const Root = createNativeStackNavigator<RootStackParamList>();

/**
 * Single NavigationContainer for the whole app. Which screens are
 * registered switches on auth status (React Navigation's recommended
 * "authentication flow" pattern), mirroring apps/customer's RootNavigator:
 * `AuthStack` while unauthenticated, `AppTabs` + `AssignmentDetail` once
 * authenticated.
 */
export function RootNavigator() {
  const { status } = useAuth();

  // Registers the device's Expo push token (once logged in) and wires
  // notification-tap navigation — see hooks/usePushNotifications.ts. No-op
  // while unauthenticated/loading.
  usePushNotifications(status === 'authenticated');

  return (
    <NavigationContainer ref={navigationRef}>
      <Root.Navigator screenOptions={{ headerShown: false }}>
        {status === 'loading' && <Root.Screen name="Loading" component={BootSplash} />}
        {status === 'unauthenticated' && <Root.Screen name="Auth" component={AuthStack} />}
        {status === 'authenticated' && (
          <>
            <Root.Screen name="Tabs" component={AppTabs} />
            <Root.Screen
              name="AssignmentDetail"
              component={AssignmentDetail}
              options={{ headerShown: true, title: 'Assignment' }}
            />
          </>
        )}
      </Root.Navigator>
    </NavigationContainer>
  );
}
