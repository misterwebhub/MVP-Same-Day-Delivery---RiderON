import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { color, space, typography } from '@rideron/design-tokens';

/**
 * Temporary placeholder for screens not yet built (Tasks #24-#28 replace
 * these one by one). Renders the navigation route so the shell can be
 * clicked through end-to-end before real screen content exists — never
 * a blank/crashing route.
 */
export function ScreenStub({ title, note }: { title: string; note?: string }) {
  return (
    <View style={styles.container}>
      <Text style={styles.title}>{title}</Text>
      {note ? <Text style={styles.note}>{note}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: color.background,
    alignItems: 'center',
    justifyContent: 'center',
    padding: space[6],
  },
  title: {
    ...typography.h1,
    color: color.textPrimary,
    textAlign: 'center',
  },
  note: {
    ...typography.body,
    color: color.textSecondary,
    textAlign: 'center',
    marginTop: space[2],
  },
});
