import React from 'react';
import { StyleSheet, View } from 'react-native';
import { color, space } from '@rideron/design-tokens';

/**
 * Horizontal segment progress across the booking flow header — per docs/07's
 * "completed = orange fill, current = orange outline, upcoming = grey" spec.
 * `current` is 1-indexed.
 */
export function StepProgress({ current, total }: { current: number; total: number }) {
  return (
    <View style={styles.row}>
      {Array.from({ length: total }, (_, i) => i + 1).map((step) => (
        <View
          key={step}
          style={[
            styles.segment,
            step < current && styles.segmentDone,
            step === current && styles.segmentCurrent,
          ]}
        />
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    gap: space[1],
    paddingHorizontal: space[6],
    paddingTop: space[4],
    paddingBottom: space[2],
  },
  segment: {
    flex: 1,
    height: 4,
    borderRadius: 2,
    backgroundColor: color.border,
  },
  segmentDone: {
    backgroundColor: color.primary,
  },
  segmentCurrent: {
    backgroundColor: color.primary,
    opacity: 0.5,
  },
});
