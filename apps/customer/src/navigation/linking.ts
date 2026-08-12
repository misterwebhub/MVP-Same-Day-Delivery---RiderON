import type { LinkingOptions } from '@react-navigation/native';
import type { RootStackParamList } from './types';

/**
 * Deep link config — riderona://orders/{id} routes to order tracking (from
 * a push notification tap or a payment-return redirect), per docs/01's
 * "Deep links" note.
 */
export const linking: LinkingOptions<RootStackParamList> = {
  prefixes: ['riderona://'],
  config: {
    screens: {
      OrderDetails: 'orders/:orderId',
      Tabs: {
        screens: {
          Home: 'home',
          Orders: 'orders',
        },
      },
    },
  },
};
