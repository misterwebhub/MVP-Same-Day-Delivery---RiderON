import React from 'react';
import { StyleSheet, View } from 'react-native';
import { color } from '@rideron/design-tokens';
import { Logo } from './Logo';

/**
 * Root-level "Loading" screen — shown by RootNavigator while AuthProvider
 * resolves stored-session state (see useAuth.tsx), before it knows whether
 * to mount AuthStack or AppTabs. Purely presentational: no navigation logic,
 * since at this point we don't yet know which stack (and param list) will
 * end up mounted. Compare features/auth/screens/Splash.tsx, which is the
 * AuthStack's own first screen and does have onboarding-check navigation.
 */
export function BootSplash() {
  return (
    <View style={styles.container}>
      <Logo size={40} variant="light" />
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
});
