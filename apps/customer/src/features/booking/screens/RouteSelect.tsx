import React, { useEffect, useState } from 'react';
import { ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { color, radius, space, typography } from '@rideron/design-tokens';
import type { City, RouteSummary, Station } from '@rideron/types';
import { apiClient } from '../../../services/httpClient';
import { Button } from '../../../components/Button';
import { Card } from '../../../components/Card';
import { Icon } from '../../../components/Icon';
import { KeyboardSafeScreen } from '../../../components/KeyboardSafeScreen';
import { useSafeBottomPadding } from '../../../hooks/useSafeBottomPadding';
import { StepProgress } from '../../../components/StepProgress';
import { formatPaise } from '../../../utils/currency';
import { useBookingDraft } from '../BookingDraftContext';
import { useAllStations } from '../hooks/useAllStations';
import { useRouteResolution } from '../hooks/useRouteResolution';
import { RouteFieldsCard } from '../components/RouteFieldsCard';
import { RoutePreviewCard } from '../components/RoutePreviewCard';
import type { BookingStackParamList } from '../../../navigation/types';

type Props = NativeStackScreenProps<BookingStackParamList, 'RouteSelect'>;

/** Origin/destination station pickers -> resolves to a RouteSummary, per docs/01's
 * "Home -> pick From/To -> Continue" journey step. Redesigned around a compact
 * From/To card with a searchable station sheet (RouteFieldsCard) plus one-tap
 * popular-route suggestions, replacing the old long scrollable chip grid. */
export function RouteSelect({ navigation }: Props) {
  const { draft, update } = useBookingDraft();
  const { stations: allStations } = useAllStations();
  const [originStation, setOriginStation] = useState<Station | null>(draft.originStation);
  const [originCity, setOriginCity] = useState<City | null>(draft.originCity);
  const [destinationStation, setDestinationStation] = useState<Station | null>(draft.destinationStation);
  const [destinationCity, setDestinationCity] = useState<City | null>(draft.destinationCity);
  const [popularRoutes, setPopularRoutes] = useState<RouteSummary[]>([]);
  const bottomPadding = useSafeBottomPadding(space[6]);

  // Manual address, captured inline the moment a Kanpur station is tapped in
  // RouteFieldsCard's station picker — same draft-then-commit pattern as the
  // station picks above, merged into the shared draft only on Continue.
  const [pickupAddressText, setPickupAddressText] = useState(draft.pickupAddressText);
  const [pickupLatitude, setPickupLatitude] = useState(draft.pickupLatitude);
  const [pickupLongitude, setPickupLongitude] = useState(draft.pickupLongitude);
  const [pickupPostalCode, setPickupPostalCode] = useState(draft.pickupPostalCode);
  const [deliveryAddressText, setDeliveryAddressText] = useState(draft.deliveryAddressText);
  const [deliveryLatitude, setDeliveryLatitude] = useState(draft.deliveryLatitude);
  const [deliveryLongitude, setDeliveryLongitude] = useState(draft.deliveryLongitude);
  const [deliveryPostalCode, setDeliveryPostalCode] = useState(draft.deliveryPostalCode);

  const { route, resolving, error } = useRouteResolution(originStation, destinationStation);

  useEffect(() => {
    apiClient.catalog
      .getPopularRoutes()
      .then(setPopularRoutes)
      .catch(() => setPopularRoutes([]));
  }, []);

  /** Popular-route suggestions only carry station ids (no nested city), so
   * resolve each side against the already-loaded full station list to get
   * a City for the draft — falls back to the bare station if the station
   * list hasn't finished loading yet. */
  const applyRoute = (r: RouteSummary) => {
    if (!r.origin_station || !r.destination_station) return;
    const originMatch = allStations.find((s) => s.id === r.origin_station!.id);
    const destinationMatch = allStations.find((s) => s.id === r.destination_station!.id);
    setOriginStation(originMatch ?? r.origin_station);
    setOriginCity(originMatch?.city ?? null);
    setDestinationStation(destinationMatch ?? r.destination_station);
    setDestinationCity(destinationMatch?.city ?? null);
  };

  const onSwap = () => {
    const os = originStation;
    const oc = originCity;
    setOriginStation(destinationStation);
    setOriginCity(destinationCity);
    setDestinationStation(os);
    setDestinationCity(oc);
    // Swap the sides' captured addresses too, so a Kanpur pickup address
    // doesn't silently reappear as a Kanpur delivery address (or vice versa)
    // after the stations flip.
    const pat = pickupAddressText;
    const plat = pickupLatitude;
    const plng = pickupLongitude;
    const ppc = pickupPostalCode;
    setPickupAddressText(deliveryAddressText);
    setPickupLatitude(deliveryLatitude);
    setPickupLongitude(deliveryLongitude);
    setPickupPostalCode(deliveryPostalCode);
    setDeliveryAddressText(pat);
    setDeliveryLatitude(plat);
    setDeliveryLongitude(plng);
    setDeliveryPostalCode(ppc);
  };

  const onContinue = () => {
    if (!route || !originCity || !originStation || !destinationCity || !destinationStation) return;
    update({
      originCity,
      originStation,
      destinationCity,
      destinationStation,
      route,
      pickupAddressText,
      pickupLatitude,
      pickupLongitude,
      pickupPostalCode,
      deliveryAddressText,
      deliveryLatitude,
      deliveryLongitude,
      deliveryPostalCode,
    });
    navigation.navigate('ParcelDetails');
  };

  return (
    <KeyboardSafeScreen style={styles.container}>
      <StepProgress current={1} total={6} />
      <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
        <Text style={styles.heading}>Where's it going?</Text>
        <Text style={styles.subheading}>Pick a pickup and a drop-off station — we'll find your route.</Text>

        <Card style={styles.fieldsCard}>
          <RouteFieldsCard
            originStation={originStation}
            destinationStation={destinationStation}
            pickupAddressText={pickupAddressText}
            deliveryAddressText={deliveryAddressText}
            onSelectOrigin={(station) => {
              setOriginStation(station);
              setOriginCity(station.city);
            }}
            onSelectDestination={(station) => {
              setDestinationStation(station);
              setDestinationCity(station.city);
            }}
            onSwap={onSwap}
            onPickupAddressCapture={(capture) => {
              setPickupAddressText(capture.text);
              setPickupLatitude(capture.latitude);
              setPickupLongitude(capture.longitude);
              setPickupPostalCode(capture.postalCode);
            }}
            onDeliveryAddressCapture={(capture) => {
              setDeliveryAddressText(capture.text);
              setDeliveryLatitude(capture.latitude);
              setDeliveryLongitude(capture.longitude);
              setDeliveryPostalCode(capture.postalCode);
            }}
          />
          <RoutePreviewCard route={route} resolving={resolving} error={error} />
        </Card>

        {popularRoutes.length > 0 ? (
          <View style={styles.suggestions}>
            <Text style={styles.suggestionsTitle}>Popular routes</Text>
            {popularRoutes.slice(0, 5).map((r) => {
              const active = originStation?.id === r.origin_station?.id && destinationStation?.id === r.destination_station?.id;
              return (
                <TouchableOpacity
                  key={r.id}
                  style={[styles.suggestionRow, active && styles.suggestionRowActive]}
                  activeOpacity={0.8}
                  onPress={() => applyRoute(r)}
                >
                  <View style={styles.suggestionIcon}>
                    <Icon name="train-outline" size={16} color={color.primary} />
                  </View>
                  <Text style={styles.suggestionText} numberOfLines={1}>
                    {r.origin_station?.name ?? '—'} → {r.destination_station?.name ?? '—'}
                  </Text>
                  <Text style={styles.suggestionPrice}>from {formatPaise(r.price_from)}</Text>
                </TouchableOpacity>
              );
            })}
          </View>
        ) : null}
      </ScrollView>
      <View style={[styles.footer, { paddingBottom: bottomPadding }]}>
        <Button title="Continue" onPress={onContinue} disabled={!route} />
      </View>
    </KeyboardSafeScreen>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: color.background,
  },
  content: {
    padding: space[5],
    paddingBottom: space[8],
  },
  heading: {
    ...typography.h1,
    color: color.textPrimary,
    marginBottom: space[1],
  },
  subheading: {
    ...typography.body,
    color: color.textSecondary,
    marginBottom: space[5],
  },
  fieldsCard: {
    marginBottom: space[5],
  },
  suggestions: {
    marginTop: space[1],
  },
  suggestionsTitle: {
    ...typography.h2,
    color: color.textPrimary,
    marginBottom: space[3],
  },
  suggestionRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space[3],
    backgroundColor: color.surface,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: color.border,
    paddingHorizontal: space[4],
    paddingVertical: space[3],
    marginBottom: space[2],
  },
  suggestionRowActive: {
    borderColor: color.primary,
    backgroundColor: color.primaryTint,
  },
  suggestionIcon: {
    width: 30,
    height: 30,
    borderRadius: radius.pill,
    backgroundColor: color.primaryTint,
    alignItems: 'center',
    justifyContent: 'center',
  },
  suggestionText: {
    ...typography.body,
    color: color.textPrimary,
    flex: 1,
  },
  suggestionPrice: {
    ...typography.caption,
    color: color.textSecondary,
  },
  footer: {
    padding: space[6],
    borderTopWidth: 1,
    borderTopColor: color.border,
    backgroundColor: color.surface,
  },
});
