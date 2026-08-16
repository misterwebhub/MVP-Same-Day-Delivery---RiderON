import React from 'react';
import { StyleSheet, TouchableOpacity } from 'react-native';
import { CommonActions, useNavigation } from '@react-navigation/native';
import { color as tokens } from '@rideron/design-tokens';
import { Icon } from './Icon';
import { useBookingDraft } from '../features/booking/BookingDraftContext';

/**
 * Resets the whole root stack to Tabs/Home, walking up through any nested
 * navigators first. Shared by HomeButton and by any screen that needs to
 * drop the customer back on Home programmatically (e.g. OrderDetails
 * auto-returning home once a delivery completes) rather than only in
 * response to a tap.
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function resetToHome(navigation: any): void {
  let root: any = navigation;
  while (root.getParent()) {
    root = root.getParent();
  }
  root.dispatch(
    CommonActions.reset({
      index: 0,
      routes: [{ name: 'Tabs', state: { routes: [{ name: 'Home' }] } }],
    }),
  );
}

interface HomeButtonProps {
  tintColor?: string;
  /** Clears in-progress booking draft state too — pass true when this button
   * lives inside the Booking flow, where "go home" means abandoning the draft
   * rather than just navigating past it (otherwise re-entering Booking later
   * would silently resume the half-finished flow the customer already left). */
  resetBookingDraft?: boolean;
}

/**
 * Persistent "go to Home" escape hatch, shown in the header of every screen
 * that isn't already one tap from Home via the bottom tab bar — the Booking
 * flow and OrderDetails cover the tab bar entirely, and Support/Profile are
 * nested stacks a few taps deep. Resets the whole root stack to Tabs/Home
 * (rather than a plain `navigate`) so back-navigation from Home never
 * re-surfaces the screen the customer just left — landing back on a stale
 * mid-booking or order-tracking screen would be confusing.
 */
export function HomeButton({ tintColor = tokens.textPrimary, resetBookingDraft = false }: HomeButtonProps) {
  const navigation = useNavigation();
  const { reset: resetDraft } = useBookingDraft();

  const goHome = () => {
    if (resetBookingDraft) resetDraft();
    resetToHome(navigation);
  };

  return (
    <TouchableOpacity
      onPress={goHome}
      style={styles.button}
      hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
      accessibilityRole="button"
      accessibilityLabel="Go to Home"
    >
      <Icon name="home-outline" size={22} color={tintColor} />
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  button: {
    paddingHorizontal: 6,
  },
});
