import React from 'react';
import { StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { color, radius, space, typography } from '@rideron/design-tokens';

interface CheckboxProps {
  checked: boolean;
  onToggle: () => void;
  label: string;
}

/** Generic reusable checkbox row — per docs/08's components/ note. */
export function Checkbox({ checked, onToggle, label }: CheckboxProps) {
  return (
    <TouchableOpacity accessibilityRole="checkbox" accessibilityState={{ checked }} style={styles.row} onPress={onToggle} activeOpacity={0.8}>
      <View style={[styles.box, checked && styles.boxChecked]}>{checked ? <Text style={styles.check}>✓</Text> : null}</View>
      <Text style={styles.label}>{label}</Text>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'flex-start',
  },
  box: {
    width: 22,
    height: 22,
    borderRadius: radius.sm,
    borderWidth: 1,
    borderColor: color.border,
    backgroundColor: color.surface,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: space[3],
    marginTop: 2,
  },
  boxChecked: {
    backgroundColor: color.primary,
    borderColor: color.primary,
  },
  check: {
    color: color.textInverse,
    fontSize: 14,
    lineHeight: 16,
  },
  label: {
    ...typography.body,
    color: color.textPrimary,
    flex: 1,
  },
});
