import React from 'react';
import { StyleSheet, Text, TouchableOpacity } from 'react-native';
import { color, radius, space, typography } from '@rideron/design-tokens';

interface ChipProps {
  label: string;
  selected?: boolean;
  onPress: () => void;
}

/** Generic reusable selectable pill — per docs/08's components/ note. */
export function Chip({ label, selected, onPress }: ChipProps) {
  return (
    <TouchableOpacity
      accessibilityRole="button"
      accessibilityState={{ selected: Boolean(selected) }}
      onPress={onPress}
      activeOpacity={0.8}
      style={[styles.chip, selected && styles.chipSelected]}
    >
      <Text style={[styles.label, selected && styles.labelSelected]}>{label}</Text>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  chip: {
    paddingHorizontal: space[4],
    paddingVertical: space[2],
    borderRadius: radius.pill,
    borderWidth: 1,
    borderColor: color.border,
    backgroundColor: color.surface,
    marginRight: space[2],
    marginBottom: space[2],
  },
  chipSelected: {
    borderColor: color.primary,
    backgroundColor: color.primaryTint,
  },
  label: {
    ...typography.caption,
    color: color.textPrimary,
  },
  labelSelected: {
    ...typography.bodyStrong,
    color: color.primaryDark,
    fontSize: 13,
  },
});
