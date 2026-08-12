import React from 'react';
import { Platform, StyleSheet, View, ViewProps } from 'react-native';
import { color, radius, space } from '@rideron/design-tokens';

/**
 * Generic white rounded surface with a soft shadow — replaces the
 * per-screen ad-hoc `TouchableOpacity`/`View` card styling that was
 * duplicated across Home, Orders, etc.
 */
export function Card({ style, ...rest }: ViewProps) {
  return <View style={[styles.card, style]} {...rest} />;
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: color.surface,
    borderRadius: radius.lg,
    padding: space[5],
    ...Platform.select({
      web: { boxShadow: '0 4px 16px rgba(10, 27, 61, 0.08)' },
      default: {
        shadowColor: color.shadow,
        shadowOpacity: 0.08,
        shadowRadius: 16,
        shadowOffset: { width: 0, height: 4 },
        elevation: 3,
      },
    }),
  },
});
