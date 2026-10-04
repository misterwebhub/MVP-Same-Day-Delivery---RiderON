import React, { useMemo, useState } from 'react';
import { ActivityIndicator, Alert, FlatList, Modal, Platform, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import * as Location from 'expo-location';
import { color, radius, space, typography } from '@rideron/design-tokens';
import { AddressAutocompleteField } from '../../../components/AddressAutocompleteField';
import { Button } from '../../../components/Button';
import { Icon } from '../../../components/Icon';
import { apiClient } from '../../../services/httpClient';
import type { StationOption } from '../hooks/useAllStations';

export interface ManualAddressCapture {
  text: string;
  latitude: number | null;
  longitude: number | null;
  postalCode: string | null;
}

/** India Post PIN codes are exactly 6 digits, first digit 1-9. */
const PINCODE_PATTERN = /^[1-9][0-9]{5}$/;

interface Section {
  title: string;
  data: StationOption[];
}

/** Full-screen "search a station" sheet — the Uber/Ola-style location picker
 * this app was missing. Replaces the old plain scrollable chip grid: one tap
 * opens this, type-to-filter narrows a couple dozen stations instantly, and
 * the currently-selected station (if any) is pinned to the top so re-opening
 * to change your mind doesn't mean re-scrolling to find it. */
export function StationPickerModal({
  visible,
  title,
  stations,
  loading,
  selectedStationId,
  disabledStationId,
  onSelect,
  onClose,
  manualAddressCities = [],
  allowCurrentLocation = false,
  onAddressCapture,
}: {
  visible: boolean;
  title: string;
  stations: StationOption[];
  loading: boolean;
  selectedStationId?: number | null;
  /** The other leg's station — shown but not selectable, so From/To can never match. */
  disabledStationId?: number | null;
  onSelect: (station: StationOption) => void;
  onClose: () => void;
  /** City names (case-insensitive) that, when picked, show an inline
   * "enter address" step before confirming the station — mirrors backend
   * config('parcel.manual_address_cities'). Everywhere else the station
   * alone is used, same as before. */
  manualAddressCities?: string[];
  /** Whether the inline address step offers "Use my current location" — only
   * makes sense for a pickup the customer is physically standing at, never
   * for a delivery address. */
  allowCurrentLocation?: boolean;
  /** Fired once the customer confirms the inline address step (or, for a
   * non-opted-in city, fired immediately with an empty/null payload so any
   * previously-captured address for the other city doesn't linger stale). */
  onAddressCapture?: (capture: ManualAddressCapture) => void;
}) {
  const [query, setQuery] = useState('');
  const [pendingStation, setPendingStation] = useState<StationOption | null>(null);
  const [addressText, setAddressText] = useState('');
  const [addressPostalCode, setAddressPostalCode] = useState<string | null>(null);
  const [addressLatitude, setAddressLatitude] = useState<number | null>(null);
  const [addressLongitude, setAddressLongitude] = useState<number | null>(null);
  const [locating, setLocating] = useState(false);
  const [locateFailed, setLocateFailed] = useState(false);

  const sections = useMemo<Section[]>(() => {
    const q = query.trim().toLowerCase();
    const filtered = q
      ? stations.filter((s) => s.name.toLowerCase().includes(q) || s.city.name.toLowerCase().includes(q))
      : stations;

    const byCity = new Map<string, StationOption[]>();
    filtered.forEach((station) => {
      const list = byCity.get(station.city.name) ?? [];
      list.push(station);
      byCity.set(station.city.name, list);
    });

    return Array.from(byCity.entries())
      .sort(([a], [b]) => a.localeCompare(b))
      .map(([cityName, data]) => ({ title: cityName, data }));
  }, [stations, query]);

  const flatData = useMemo(() => {
    const rows: Array<{ type: 'header'; title: string } | { type: 'station'; station: StationOption }> = [];
    sections.forEach((section) => {
      rows.push({ type: 'header', title: section.title });
      section.data.forEach((station) => rows.push({ type: 'station', station }));
    });
    return rows;
  }, [sections]);

  const resetAddressState = () => {
    setPendingStation(null);
    setAddressText('');
    setAddressPostalCode(null);
    setAddressLatitude(null);
    setAddressLongitude(null);
    setLocateFailed(false);
  };

  const pincodeValid = addressPostalCode !== null && PINCODE_PATTERN.test(addressPostalCode.trim());

  const onPressStation = (station: StationOption) => {
    const isManualAddressCity = manualAddressCities.some((city) => city.toLowerCase() === station.city.name.trim().toLowerCase());
    if (isManualAddressCity && onAddressCapture) {
      setPendingStation(station);
      return;
    }
    // Non-opted-in city (or no capture handler wired) — clear any stale
    // address from a previous selection and confirm the station immediately,
    // same behavior as before this feature existed.
    onAddressCapture?.({ text: '', latitude: null, longitude: null, postalCode: null });
    onSelect(station);
  };

  const onConfirmAddress = () => {
    if (!pendingStation || !pincodeValid) return;
    onAddressCapture?.({
      text: addressText.trim(),
      latitude: addressLatitude,
      longitude: addressLongitude,
      postalCode: addressPostalCode!.trim(),
    });
    onSelect(pendingStation);
    resetAddressState();
  };

  const onUseCurrentLocation = async () => {
    setLocating(true);
    setLocateFailed(false);
    try {
      const { status } = await Location.getForegroundPermissionsAsync();
      let granted = status === 'granted';
      if (!granted) {
        const requested = await Location.requestForegroundPermissionsAsync();
        granted = requested.status === 'granted';
      }
      if (!granted) {
        Alert.alert('Location permission needed', 'Allow location access so the rider can find this point.');
        setLocateFailed(true);
        return;
      }
      const position = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced });
      const { latitude, longitude } = position.coords;
      setAddressLatitude(latitude);
      setAddressLongitude(longitude);
      // Zomato/Porter-style: don't just capture a raw coordinate — turn it
      // into a readable address + pincode right away so the customer sees
      // something concrete to confirm or edit, instead of a blank field.
      try {
        const geocoded = await apiClient.places.reverseGeocode(latitude, longitude);
        if (geocoded.formatted_address) {
          setAddressText(geocoded.formatted_address);
          setAddressPostalCode(geocoded.postal_code);
        }
      } catch {
        // Coordinate is still captured even if reverse-geocoding fails —
        // the customer can just type the address manually below.
      }
    } catch {
      Alert.alert('Could not get location', 'Please try again, or just describe the address in words below.');
      setLocateFailed(true);
    } finally {
      setLocating(false);
    }
  };

  const onModalClose = () => {
    resetAddressState();
    onClose();
  };

  if (pendingStation) {
    return (
      <Modal visible={visible} animationType="slide" presentationStyle="pageSheet" onRequestClose={onModalClose}>
        <SafeAreaView style={styles.screen} edges={['top', 'bottom']}>
          <View style={styles.header}>
            <TouchableOpacity accessibilityRole="button" accessibilityLabel="Back to station list" onPress={resetAddressState} hitSlop={8} style={styles.closeButton}>
              <Icon name="arrow-back" size={20} color={color.textPrimary} />
            </TouchableOpacity>
            <Text style={styles.headerTitle}>{title}</Text>
            <TouchableOpacity accessibilityRole="button" accessibilityLabel="Close" onPress={onModalClose} hitSlop={8} style={styles.closeButton}>
              <Icon name="close" size={20} color={color.textPrimary} />
            </TouchableOpacity>
          </View>
          <View style={styles.addressStep}>
            <Text style={styles.addressStepStation}>{pendingStation.name}</Text>
            <Text style={styles.addressStepHint}>
              Tell us exactly where to {allowCurrentLocation ? 'come' : 'deliver'} — pincode is required, address helps the rider.
            </Text>

            {allowCurrentLocation ? (
              <>
                <TouchableOpacity
                  style={styles.locateCard}
                  activeOpacity={0.75}
                  onPress={onUseCurrentLocation}
                  disabled={locating}
                  accessibilityRole="button"
                >
                  <View style={styles.locateIcon}>
                    {locating ? <ActivityIndicator size="small" color={color.primary} /> : <Icon name="locate" size={18} color={color.primary} />}
                  </View>
                  <View style={styles.locateText}>
                    <Text style={styles.locateTitle}>{addressLatitude !== null ? 'Location found — tap to refresh' : 'Use my current location'}</Text>
                    <Text style={styles.locateSubtitle} numberOfLines={2}>
                      {locating
                        ? 'Finding you…'
                        : addressLatitude !== null
                          ? addressPostalCode
                            ? `Pincode ${addressPostalCode} detected — edit below if needed`
                            : 'Detected — edit the address below if needed'
                          : "We'll auto-fill the address and pincode nearby"}
                    </Text>
                  </View>
                  {addressLatitude !== null ? <Icon name="checkmark-circle" size={20} color={color.success} /> : null}
                </TouchableOpacity>
                {locateFailed ? <Text style={styles.locateError}>Couldn't detect your location — type the address below instead.</Text> : null}
                <View style={styles.orDivider}>
                  <View style={styles.orLine} />
                  <Text style={styles.orText}>OR SEARCH MANUALLY</Text>
                  <View style={styles.orLine} />
                </View>
              </>
            ) : null}

            <View style={styles.addressField}>
              <AddressAutocompleteField
                label="Address"
                value={addressText}
                onChangeText={(text) => {
                  setAddressText(text);
                  setAddressPostalCode(null);
                }}
                onCoordinateResolved={(latitude, longitude) => {
                  setAddressLatitude(latitude);
                  setAddressLongitude(longitude);
                }}
                originBias={addressLatitude !== null && addressLongitude !== null
                  ? { latitude: addressLatitude, longitude: addressLongitude }
                  : { latitude: Number(pendingStation.latitude), longitude: Number(pendingStation.longitude) }}
                placeholder="Search or type House / street / area"
                multiline
              />
            </View>

            <View style={styles.pincodeField}>
              <Text style={styles.pincodeLabel}>Pincode *</Text>
              <TextInput
                style={[styles.pincodeInput, addressPostalCode && !pincodeValid && styles.pincodeInputError]}
                value={addressPostalCode ?? ''}
                onChangeText={(text) => setAddressPostalCode(text.replace(/[^0-9]/g, '').slice(0, 6))}
                placeholder="6-digit pincode"
                placeholderTextColor={color.textSecondary}
                keyboardType="number-pad"
                maxLength={6}
              />
              {addressPostalCode && !pincodeValid ? (
                <Text style={styles.pincodeError}>Enter a valid 6-digit pincode.</Text>
              ) : null}
            </View>
          </View>
          <View style={styles.addressFooter}>
            <Button title="Continue" onPress={onConfirmAddress} disabled={!pincodeValid} />
          </View>
        </SafeAreaView>
      </Modal>
    );
  }

  return (
    <Modal visible={visible} animationType="slide" presentationStyle="pageSheet" onRequestClose={onModalClose}>
      <SafeAreaView style={styles.screen} edges={['top', 'bottom']}>
        <View style={styles.header}>
          <Text style={styles.headerTitle}>{title}</Text>
          <TouchableOpacity accessibilityRole="button" accessibilityLabel="Close" onPress={onModalClose} hitSlop={8} style={styles.closeButton}>
            <Icon name="close" size={20} color={color.textPrimary} />
          </TouchableOpacity>
        </View>

        <View style={styles.searchBar}>
          <Icon name="search" size={18} color={color.textSecondary} />
          <TextInput
            style={styles.searchInput}
            placeholder="Search a station or city"
            placeholderTextColor={color.textSecondary}
            value={query}
            onChangeText={setQuery}
            autoCorrect={false}
            autoFocus={Platform.OS !== 'web'}
          />
          {query.length > 0 ? (
            <TouchableOpacity onPress={() => setQuery('')} hitSlop={8}>
              <Icon name="close-circle" size={18} color={color.textSecondary} />
            </TouchableOpacity>
          ) : null}
        </View>

        {loading ? (
          <View style={styles.emptyState}>
            <Text style={styles.emptyStateText}>Loading stations…</Text>
          </View>
        ) : (
          <FlatList
            data={flatData}
            keyExtractor={(item, index) => (item.type === 'header' ? `h-${item.title}` : `s-${item.station.id}`) + index}
            keyboardShouldPersistTaps="handled"
            contentContainerStyle={styles.listContent}
            ListEmptyComponent={
              <View style={styles.emptyState}>
                <Icon name="location-outline" size={28} color={color.textSecondary} />
                <Text style={styles.emptyStateText}>No stations match "{query}"</Text>
              </View>
            }
            renderItem={({ item }) => {
              if (item.type === 'header') {
                return <Text style={styles.sectionHeader}>{item.title}</Text>;
              }
              const station = item.station;
              const selected = selectedStationId === station.id;
              const disabled = disabledStationId === station.id;
              return (
                <TouchableOpacity
                  style={[styles.row, selected && styles.rowSelected, disabled && styles.rowDisabled]}
                  activeOpacity={disabled ? 1 : 0.75}
                  disabled={disabled}
                  onPress={() => onPressStation(station)}
                  accessibilityRole="button"
                  accessibilityState={{ selected, disabled }}
                >
                  <View style={[styles.rowIcon, selected && styles.rowIconSelected]}>
                    <Icon name="train" size={16} color={selected ? color.textInverse : color.primary} />
                  </View>
                  <View style={styles.rowText}>
                    <Text style={[styles.rowTitle, disabled && styles.rowTitleDisabled]}>{station.name}</Text>
                    <Text style={styles.rowSubtitle}>{station.city.name}</Text>
                  </View>
                  {selected ? <Icon name="checkmark-circle" size={20} color={color.primary} /> : null}
                  {disabled ? <Text style={styles.rowDisabledLabel}>selected on other side</Text> : null}
                </TouchableOpacity>
              );
            }}
          />
        )}
      </SafeAreaView>
    </Modal>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: color.surface,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: space[5],
    paddingTop: space[4],
    paddingBottom: space[3],
  },
  headerTitle: {
    ...typography.h1,
    color: color.textPrimary,
  },
  closeButton: {
    width: 32,
    height: 32,
    borderRadius: radius.pill,
    backgroundColor: color.background,
    alignItems: 'center',
    justifyContent: 'center',
  },
  searchBar: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space[2],
    marginHorizontal: space[5],
    marginBottom: space[3],
    paddingHorizontal: space[4],
    height: 46,
    borderRadius: radius.md,
    backgroundColor: color.background,
  },
  searchInput: {
    flex: 1,
    ...typography.body,
    color: color.textPrimary,
    height: '100%',
  },
  listContent: {
    paddingHorizontal: space[5],
    paddingBottom: space[8],
  },
  sectionHeader: {
    ...typography.micro,
    color: color.textSecondary,
    marginTop: space[4],
    marginBottom: space[2],
    letterSpacing: 0.6,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space[3],
    paddingVertical: space[3],
    paddingHorizontal: space[3],
    borderRadius: radius.md,
    marginBottom: space[1],
  },
  rowSelected: {
    backgroundColor: color.primaryTint,
  },
  rowDisabled: {
    opacity: 0.4,
  },
  rowIcon: {
    width: 34,
    height: 34,
    borderRadius: radius.pill,
    backgroundColor: color.primaryTint,
    alignItems: 'center',
    justifyContent: 'center',
  },
  rowIconSelected: {
    backgroundColor: color.primary,
  },
  rowText: {
    flex: 1,
  },
  rowTitle: {
    ...typography.bodyStrong,
    color: color.textPrimary,
  },
  rowTitleDisabled: {
    color: color.textSecondary,
  },
  rowSubtitle: {
    ...typography.caption,
    color: color.textSecondary,
    marginTop: 1,
  },
  rowDisabledLabel: {
    ...typography.micro,
    color: color.textSecondary,
  },
  emptyState: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingTop: space[8],
    gap: space[2],
  },
  emptyStateText: {
    ...typography.body,
    color: color.textSecondary,
  },
  addressStep: {
    flex: 1,
    paddingHorizontal: space[5],
    paddingTop: space[2],
  },
  addressStepStation: {
    ...typography.h2,
    color: color.textPrimary,
    marginBottom: space[2],
  },
  addressStepHint: {
    ...typography.caption,
    color: color.textSecondary,
    marginBottom: space[5],
  },
  addressField: {
    marginBottom: space[4],
  },
  pincodeField: {
    marginBottom: space[4],
  },
  pincodeLabel: {
    ...typography.caption,
    color: color.textSecondary,
    marginBottom: space[1],
  },
  pincodeInput: {
    ...typography.body,
    color: color.textPrimary,
    borderWidth: 1,
    borderColor: color.border,
    borderRadius: radius.md,
    paddingHorizontal: space[4],
    height: 46,
  },
  pincodeInputError: {
    borderColor: color.error,
  },
  pincodeError: {
    ...typography.caption,
    color: color.error,
    marginTop: space[1],
  },
  locateCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space[3],
    padding: space[4],
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: color.primary,
    backgroundColor: color.primaryTint,
    marginBottom: space[2],
  },
  locateIcon: {
    width: 34,
    height: 34,
    borderRadius: radius.pill,
    backgroundColor: color.surface,
    alignItems: 'center',
    justifyContent: 'center',
  },
  locateText: {
    flex: 1,
  },
  locateTitle: {
    ...typography.bodyStrong,
    color: color.textPrimary,
  },
  locateSubtitle: {
    ...typography.caption,
    color: color.textSecondary,
    marginTop: 1,
  },
  locateError: {
    ...typography.caption,
    color: color.error,
    marginBottom: space[2],
  },
  orDivider: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space[2],
    marginVertical: space[3],
  },
  orLine: {
    flex: 1,
    height: 1,
    backgroundColor: color.border,
  },
  orText: {
    ...typography.micro,
    color: color.textSecondary,
    letterSpacing: 0.6,
  },
  addressFooter: {
    paddingHorizontal: space[5],
    paddingBottom: space[4],
    paddingTop: space[2],
  },
});
