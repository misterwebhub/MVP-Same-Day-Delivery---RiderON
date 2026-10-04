import { useSafeAreaInsets } from 'react-native-safe-area-context';

/**
 * Fixes a mobile bug: bottom-fixed CTA buttons (footer bars) used a
 * hardcoded padding value and got drawn under the 3-button/gesture
 * navigation bar on devices whose nav buttons are on-screen rather than
 * physical. Add this on top of the screen's existing base padding, e.g.:
 *
 *   const bottomPadding = useSafeBottomPadding(space[6]);
 *   <View style={[styles.footer, { paddingBottom: bottomPadding }]}>
 */
export function useSafeBottomPadding(basePadding: number): number {
  const insets = useSafeAreaInsets();

  return basePadding + insets.bottom;
}
