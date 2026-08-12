import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { color, typography } from '@rideron/design-tokens';

/**
 * Root-level "Loading" screen — shown by RootNavigator while AuthProvider
 * resolves stored-session state (see useAuth.tsx), before it knows whether
 * to mount AuthStack or AppTabs. Mirrors apps/customer's BootSplash.
 */
export function BootSplash() {
  return (
    <View style={styles.container}>
      <Text style={styles.logo}>RiderON Partner</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: color.secondary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  logo: {
    ...typography.display,
    color: color.textInverse,
  },
});
