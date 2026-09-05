import { useEffect, useRef } from 'react';
import { Platform } from 'react-native';
import * as Notifications from 'expo-notifications';
import Constants from 'expo-constants';
import { apiClient } from '../services/httpClient';
import { ApiClientError } from '@rideron/api-client';
import { navigationRef } from '../navigation/navigationRef';

Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowBanner: true,
    shouldShowList: true,
    shouldPlaySound: true,
    shouldSetBadge: false,
  }),
});

/**
 * Registers this device for push (currently: "your parcel has been
 * delivered" — see backend/app/Services/Orders/OrderOtpVerificationService.php)
 * and handles notification taps by deep-linking straight to that order's
 * OrderDetails screen. Mirrors apps/partner's identical hook.
 *
 * Only active once `enabled` (i.e. authenticated) — no user id to attach a
 * Device row to before login.
 */
export function usePushNotifications(enabled: boolean): void {
  const registered = useRef(false);

  useEffect(() => {
    if (!enabled || registered.current) {
      return;
    }
    registered.current = true;

    (async () => {
      try {
        if (Platform.OS === 'android') {
          await Notifications.setNotificationChannelAsync('default', {
            name: 'default',
            importance: Notifications.AndroidImportance.HIGH,
          });
        }

        const { status: existing } = await Notifications.getPermissionsAsync();
        let finalStatus = existing;
        if (existing !== 'granted') {
          const { status } = await Notifications.requestPermissionsAsync();
          finalStatus = status;
        }
        if (finalStatus !== 'granted') {
          return;
        }

        const projectId = Constants.expoConfig?.extra?.eas?.projectId ?? Constants.easConfig?.projectId;
        if (!projectId) {
          // No EAS project configured yet (see `eas init`) — Expo can't mint
          // a push token without one. Fails soft: the rest of the app works
          // fine, just without push until this is set up.
          console.warn('[usePushNotifications] No EAS projectId configured — skipping push token registration.');
          return;
        }

        const { data: token } = await Notifications.getExpoPushTokenAsync({ projectId });
        await apiClient.notifications.registerDevice({ token, platform: Platform.OS });
      } catch (e) {
        console.warn('[usePushNotifications] Push registration failed (non-fatal):', e instanceof ApiClientError ? e.message : e);
      }
    })();
  }, [enabled]);

  useEffect(() => {
    const subscription = Notifications.addNotificationResponseReceivedListener((response) => {
      const data = response.notification.request.content.data as { screen?: string; order_id?: number } | undefined;
      if (!data?.screen || !navigationRef.isReady()) {
        return;
      }

      if (data.screen === 'OrderDetails' && data.order_id) {
        navigationRef.navigate('OrderDetails', { orderId: data.order_id });
      }
    });

    return () => subscription.remove();
  }, []);
}
