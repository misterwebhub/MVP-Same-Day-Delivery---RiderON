import React from 'react';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { color } from '@rideron/design-tokens';
import { RouteSelect } from '../features/booking/screens/RouteSelect';
import { ParcelDetails } from '../features/booking/screens/ParcelDetails';
import { ContactDetails } from '../features/booking/screens/ContactDetails';
import { TimeSlot } from '../features/booking/screens/TimeSlot';
import { BookingSummary } from '../features/booking/screens/BookingSummary';
import { Payment } from '../features/booking/screens/Payment';
import { Confirmation } from '../features/booking/screens/Confirmation';
import { BookingDraftProvider } from '../features/booking/BookingDraftContext';
import type { BookingStackParamList } from './types';

const Stack = createNativeStackNavigator<BookingStackParamList>();

/**
 * RouteSelect -> ... -> Confirmation, one flow with a StepProgress header
 * (docs/07's StepProgress component, wired in Task #26). Pushed from Home
 * or the BookParcel tab CTA (see AppTabs.tsx) as a full-screen stack that
 * covers the tab bar, per docs/01's navigation architecture section.
 * Wrapped in BookingDraftProvider so every screen in the flow shares one
 * persisted draft (see BookingDraftContext.tsx).
 */
export function BookingStack() {
  return (
    <BookingDraftProvider>
      <Stack.Navigator
        initialRouteName="RouteSelect"
        screenOptions={{
          headerStyle: { backgroundColor: color.surface },
          headerTintColor: color.textPrimary,
          headerBackTitle: '',
        }}
      >
        <Stack.Screen name="RouteSelect" component={RouteSelect} options={{ title: 'Select Route' }} />
        <Stack.Screen name="ParcelDetails" component={ParcelDetails} options={{ title: 'Parcel Details' }} />
        <Stack.Screen name="ContactDetails" component={ContactDetails} options={{ title: 'Sender & Receiver' }} />
        <Stack.Screen name="TimeSlot" component={TimeSlot} options={{ title: 'Delivery Time Slot' }} />
        <Stack.Screen name="BookingSummary" component={BookingSummary} options={{ title: 'Booking Summary' }} />
        <Stack.Screen name="Payment" component={Payment} options={{ title: 'Payment', gestureEnabled: false }} />
        <Stack.Screen
          name="Confirmation"
          component={Confirmation}
          options={{ title: 'Confirmed', headerBackVisible: false, gestureEnabled: false }}
        />
      </Stack.Navigator>
    </BookingDraftProvider>
  );
}
