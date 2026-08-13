import React, { useState } from 'react';
import { ActivityIndicator, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { color, radius, space, typography } from '@rideron/design-tokens';
import type { Station } from '@rideron/types';
import { Icon } from '../../../components/Icon';
import { useAllStations, type StationOption } from '../hooks/useAllStations';
import { StationPickerModal } from './StationPickerModal';

/**
 * The From/To picker used on both Home (quick-pick, no dedicated screen) and
 * RouteSelect (full flow). A tap opens the searchable StationPickerModal
 * instead of scrolling a flat chip wall — the same interaction pattern
 * riders already know from Uber/Ola/Rapido. A swap button flips both legs
 * in one tap, which the old chip-grid UI never offered.
 */
export function RouteFieldsCard({
  originStation,
  destinationStation,
  onSelectOrigin,
  onSelectDestination,
  onSwap,
}: {
  originStation: Station | null;
  destinationStation: Station | null;
  onSelectOrigin: (station: StationOption) => void;
  onSelectDestination: (station: StationOption) => void;
  onSwap: () => void;
}) {
  const { stations, loading } = useAllStations();
  const [pickerOpenFor, setPickerOpenFor] = useState<'origin' | 'destination' | null>(null);

  return (
    <View style={styles.wrapper}>
      <View style={styles.fieldStack}>
        <View style={styles.railColumn}>
          <View style={styles.railDotOrigin} />
          <View style={styles.railLine} />
          <View style={styles.railDotDestination} />
        </View>

        <View style={styles.fields}>
          <TouchableOpacity
            style={styles.field}
            activeOpacity={0.75}
            onPress={() => setPickerOpenFor('origin')}
            accessibilityRole="button"
            accessibilityLabel="Choose pickup station"
          >
            <Text style={styles.fieldLabel}>PICKUP FROM</Text>
            <Text style={[styles.fieldValue, !originStation && styles.fieldPlaceholder]} numberOfLines={1}>
              {originStation?.name ?? 'Choose pickup station'}
            </Text>
          </TouchableOpacity>

          <View style={styles.divider} />

          <TouchableOpacity
            style={styles.field}
            activeOpacity={0.75}
            onPress={() => setPickerOpenFor('destination')}
            accessibilityRole="button"
            accessibilityLabel="Choose drop-off station"
          >
            <Text style={styles.fieldLabel}>DROP AT</Text>
            <Text style={[styles.fieldValue, !destinationStation && styles.fieldPlaceholder]} numberOfLines={1}>
              {destinationStation?.name ?? 'Choose drop-off station'}
            </Text>
          </TouchableOpacity>
        </View>

        <TouchableOpacity
          style={styles.swapButton}
          activeOpacity={0.75}
          onPress={onSwap}
          disabled={!originStation && !destinationStation}
          accessibilityRole="button"
          accessibilityLabel="Swap pickup and drop-off"
        >
          {loading ? <ActivityIndicator size="small" color={color.primary} /> : <Icon name="swap-vertical" size={18} color={color.primary} />}
        </TouchableOpacity>
      </View>

      <StationPickerModal
        visible={pickerOpenFor === 'origin'}
        title="Pickup from"
        stations={stations}
        loading={loading}
        selectedStationId={originStation?.id}
        disabledStationId={destinationStation?.id}
        onSelect={(station) => {
          onSelectOrigin(station);
          setPickerOpenFor(null);
        }}
        onClose={() => setPickerOpenFor(null)}
      />
      <StationPickerModal
        visible={pickerOpenFor === 'destination'}
        title="Drop at"
        stations={stations}
        loading={loading}
        selectedStationId={destinationStation?.id}
        disabledStationId={originStation?.id}
        onSelect={(station) => {
          onSelectDestination(station);
          setPickerOpenFor(null);
        }}
        onClose={() => setPickerOpenFor(null)}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper: {
    marginBottom: space[3],
  },
  fieldStack: {
    flexDirection: 'row',
    alignItems: 'stretch',
    backgroundColor: color.background,
    borderRadius: radius.md,
    paddingVertical: space[1],
  },
  railColumn: {
    width: 24,
    alignItems: 'center',
    paddingVertical: space[4],
  },
  railDotOrigin: {
    width: 9,
    height: 9,
    borderRadius: radius.pill,
    backgroundColor: color.primary,
  },
  railLine: {
    flex: 1,
    width: 2,
    backgroundColor: color.border,
    marginVertical: 4,
  },
  railDotDestination: {
    width: 9,
    height: 9,
    borderRadius: 2,
    backgroundColor: color.secondary,
  },
  fields: {
    flex: 1,
  },
  field: {
    paddingVertical: space[3],
  },
  fieldLabel: {
    ...typography.micro,
    color: color.textSecondary,
    letterSpacing: 0.6,
    marginBottom: 2,
  },
  fieldValue: {
    ...typography.bodyStrong,
    color: color.textPrimary,
  },
  fieldPlaceholder: {
    color: color.textSecondary,
    fontFamily: typography.body.fontFamily,
  },
  divider: {
    height: 1,
    backgroundColor: color.border,
  },
  swapButton: {
    width: 40,
    height: 40,
    borderRadius: radius.pill,
    backgroundColor: color.surface,
    alignItems: 'center',
    justifyContent: 'center',
    alignSelf: 'center',
    marginLeft: space[2],
  },
});
