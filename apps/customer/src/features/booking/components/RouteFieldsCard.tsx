import React, { useState } from 'react';
import { ActivityIndicator, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { color, radius, space, typography } from '@rideron/design-tokens';
import type { Station } from '@rideron/types';
import { Icon } from '../../../components/Icon';
import { useAllStations, type StationOption } from '../hooks/useAllStations';
import { MANUAL_ADDRESS_CITIES } from '../manualAddressCities';
import { StationPickerModal, type ManualAddressCapture } from './StationPickerModal';

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
  onPickupAddressCapture,
  onDeliveryAddressCapture,
  pickupAddressText,
  deliveryAddressText,
}: {
  originStation: Station | null;
  destinationStation: Station | null;
  onSelectOrigin: (station: StationOption) => void;
  onSelectDestination: (station: StationOption) => void;
  onSwap: () => void;
  /** Fired when the pickup (Pickup From) station is confirmed — carries
   * whatever manual address was entered inline (empty/null if the picked
   * city doesn't support manual address, or none was entered). */
  onPickupAddressCapture?: (capture: ManualAddressCapture) => void;
  /** Same as above, for the drop-off (Drop At) station. */
  onDeliveryAddressCapture?: (capture: ManualAddressCapture) => void;
  /** Once the customer captures a manual pickup address (Zomato/Porter-style
   * flow), show that full address as the field's headline instead of the
   * bare station name — with the station's city kept as a small subtitle
   * underneath so context isn't lost. */
  pickupAddressText?: string | null;
  /** Same as above, for the drop-off (Drop At) field. */
  deliveryAddressText?: string | null;
}) {
  const { stations, loading } = useAllStations();
  const [pickerOpenFor, setPickerOpenFor] = useState<'origin' | 'destination' | null>(null);

  // With only two stations in the whole network (Kanpur/Lucknow today),
  // picking one side leaves exactly one valid choice for the other — so
  // auto-select it instead of making the customer tap twice. This naturally
  // stops applying (falls back to manual selection) once a 3rd station
  // exists, since then there's no single "remaining" station to infer.
  const autoSelectPaired = (picked: StationOption, other: 'origin' | 'destination') => {
    if (stations.length !== 2) return;
    const remaining = stations.find((s) => s.id !== picked.id);
    if (!remaining) return;
    if (other === 'destination') {
      onSelectDestination(remaining);
    } else {
      onSelectOrigin(remaining);
    }
  };

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
            {pickupAddressText && originStation ? (
              <>
                <Text style={styles.fieldValue} numberOfLines={2}>
                  {pickupAddressText}
                </Text>
                <Text style={styles.fieldSubtitle} numberOfLines={1}>
                  {originStation.name}
                </Text>
              </>
            ) : (
              <Text style={[styles.fieldValue, !originStation && styles.fieldPlaceholder]} numberOfLines={1}>
                {originStation?.name ?? 'Choose pickup station'}
              </Text>
            )}
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
            {deliveryAddressText && destinationStation ? (
              <>
                <Text style={styles.fieldValue} numberOfLines={2}>
                  {deliveryAddressText}
                </Text>
                <Text style={styles.fieldSubtitle} numberOfLines={1}>
                  {destinationStation.name}
                </Text>
              </>
            ) : (
              <Text style={[styles.fieldValue, !destinationStation && styles.fieldPlaceholder]} numberOfLines={1}>
                {destinationStation?.name ?? 'Choose drop-off station'}
              </Text>
            )}
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
        manualAddressCities={MANUAL_ADDRESS_CITIES}
        allowCurrentLocation
        onAddressCapture={onPickupAddressCapture}
        onSelect={(station) => {
          onSelectOrigin(station);
          autoSelectPaired(station, 'destination');
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
        manualAddressCities={MANUAL_ADDRESS_CITIES}
        onAddressCapture={onDeliveryAddressCapture}
        onSelect={(station) => {
          onSelectDestination(station);
          autoSelectPaired(station, 'origin');
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
  fieldSubtitle: {
    fontSize: 11,
    lineHeight: 14,
    fontFamily: typography.caption.fontFamily,
    color: color.textSecondary,
    marginTop: 1,
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
