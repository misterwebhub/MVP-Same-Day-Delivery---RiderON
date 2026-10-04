import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { AuthStack } from './AuthStack';
import { AppTabs } from './AppTabs';
import { BookingStack } from './BookingStack';
import { OrderDetails } from '../features/orders/screens/OrderDetails';
import { BootSplash } from '../components/BootSplash';
import { HomeButton } from '../components/HomeButton';
import { useAuth } from '../hooks/useAuth';
import { usePushNotifications } from '../hooks/usePushNotifications';
import { linking } from './linking';
import { navigationRef } from './navigationRef';
import type { RootStackParamList } from './types';

const Root = createNativeStackNavigator<RootStackParamList>();

/**
 * Single NavigationContainer for the whole app. Which screens are
 * registered switches on auth status (React Navigation's recommended
 * "authentication flow" pattern — swap the screen list, not the container),
 * per docs/01: `AuthStack` while unauthenticated, `AppTabs` + `BookingStack`
 * + `OrderDetails` once authenticated.
 */
export function RootNavigator() {
  const { status } = useAuth();

  // Registers the device's Expo push token (once logged in) and wires
  // notification-tap navigation — see hooks/usePushNotifications.ts. No-op
  // while unauthenticated/loading.
  usePushNotifications(status === 'authenticated');

  return (
    <NavigationContainer linking={linking} ref={navigationRef}>
      <Root.Navigator screenOptions={{ headerShown: false }}>
        {status === 'loading' && <Root.Screen name="Loading" component={BootSplash} />}
        {status === 'unauthenticated' && <Root.Screen name="Auth" component={AuthStack} />}
        {status === 'authenticated' && (
          <>
            <Root.Screen name="Tabs" component={AppTabs} />
            <Root.Screen name="Booking" component={BookingStack} options={{ presentation: 'modal' }} />
            <Root.Screen
              name="OrderDetails"
              component={OrderDetails}
              options={{ headerShown: true, title: 'Order', headerRight: () => <HomeButton /> }}
            />
          </>
        )}
      </Root.Navigator>
    </NavigationContainer>
  );
}
