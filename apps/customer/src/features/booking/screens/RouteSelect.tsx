import React, { useEffect, useState } from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { color, radius, space, typography } from '@rideron/design-tokens';
import type { City, RouteSummary, Station } from '@rideron/types';
import { apiClient } from '../../../services/httpClient';
import { ApiClientError } from '@rideron/api-client';
import { Button } from '../../../components/Button';
import { Chip } from '../../../components/Chip';
import { StepProgress } from '../../../components/StepProgress';
import { formatPaise } from '../../../utils/currency';
import { useBookingDraft } from '../BookingDraftContext';
import type { BookingStackParamList } from '../../../navigation/types';

type Props = NativeStackScreenProps<BookingStackParamList, 'RouteSelect'>;

interface StationOption extends Station {
  city: City;
}

/** Flat, single-tap station list — no city intermediate step. All stations across
 * every served city are loaded once and shown directly, per the "no multi-level
 * picker, finish booking fast" requirement. */
function useAllStations() {
  const [stations, setStations] = useState<StationOption[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;
    apiClient.catalog
      .listCities()
      .then(async (cities) => {
        const perCity = await Promise.all(
          cities.map((city) => apiClient.catalog.listStations(city.id).then((list) => list.map((s) => ({ ...s, city })))),
        );
        if (!cancelled) setStations(perCity.flat());
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, []);

  return { stations, loading };
}

function StationPicker({
  label,
  stations,
  loading,
  selectedStation,
  onSelectStation,
}: {
  label: string;
  stations: StationOption[];
  loading: boolean;
  selectedStation: Station | null;
  onSelectStation: (station: StationOption) => void;
}) {
  return (
    <View style={styles.pickerSection}>
      <Text style={styles.pickerLabel}>{label}</Text>
      {loading ? <ActivityIndicator style={styles.stationLoader} color={color.primary} /> : null}
      {!loading ? (
        <View style={styles.chipRow}>
          {stations.map((station) => (
            <Chip
              key={station.id}
              label={station.name}
              selected={selectedStation?.id === station.id}
              onPress={() => onSelectStation(station)}
            />
          ))}
        </View>
      ) : null}
    </View>
  );
}

/** Origin/destination city+station pickers -> resolves to a RouteSummary, per docs/01's "Home -> pick From/To -> Continue" journey step. */
export function RouteSelect({ navigation }: Props) {
  const { draft, update } = useBookingDraft();
  const { stations: allStations, loading: loadingStations } = useAllStations();
  const [originCity, setOriginCity] = useState<City | null>(draft.originCity);
  const [originStation, setOriginStation] = useState<Station | null>(draft.originStation);
  const [destinationCity, setDestinationCity] = useState<City | null>(draft.destinationCity);
  const [destinationStation, setDestinationStation] = useState<Station | null>(draft.destinationStation);
  const [route, setRoute] = useState<RouteSummary | null>(draft.route);
  const [resolving, setResolving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setRoute(null);
    setError(null);
    if (!originStation || !destinationStation) return;
    if (originStation.id === destinationStation.id) {
      setError('Pickup and drop-off stations must be different.');
      return;
    }
    let cancelled = false;
    setResolving(true);
    apiClient.catalog
      .findRoute({ origin_station_id: originStation.id, destination_station_id: destinationStation.id })
      .then((foundRoute) => {
        if (!cancelled) setRoute(foundRoute);
      })
      .catch((e) => {
        if (cancelled) return;
        if (e instanceof ApiClientError && e.status === 404) {
          setError('No direct route runs between these stations yet.');
        } else {
          setError(e instanceof ApiClientError ? e.message : 'Could not load routes.');
        }
      })
      .finally(() => {
        if (!cancelled) setResolving(false);
      });
    return () => {
      cancelled = true;
    };
  }, [originStation, destinationStation]);

  const onContinue = () => {
    if (!route || !originCity || !originStation || !destinationCity || !destinationStation) return;
    update({ originCity, originStation, destinationCity, destinationStation, route });
    navigation.navigate('ParcelDetails');
  };

  return (
    <View style={styles.container}>
      <StepProgress current={1} total={6} />
      <ScrollView contentContainerStyle={styles.content}>
        <StationPicker
          label="From"
          stations={allStations}
          loading={loadingStations}
          selectedStation={originStation}
          onSelectStation={(station) => {
            setOriginStation(station);
            setOriginCity(station.city);
          }}
        />
        <StationPicker
          label="To"
          stations={allStations}
          loading={loadingStations}
          selectedStation={destinationStation}
          onSelectStation={(station) => {
            setDestinationStation(station);
            setDestinationCity(station.city);
          }}
        />

        {resolving ? <ActivityIndicator color={color.primary} style={styles.routeLoader} /> : null}
        {error ? <Text style={styles.error}>{error}</Text> : null}

        {route ? (
          <View style={styles.routeCard}>
            <Text style={styles.routeCardTitle}>
              {originStation?.name} → {destinationStation?.name}
            </Text>
            <View style={styles.routeCardRow}>
              <Text style={styles.routeCardMeta}>{route.distance_km} km</Text>
              <Text style={styles.routeCardMeta}>~{route.estimated_duration_minutes} min</Text>
              <Text style={styles.routeCardMeta}>Cutoff {route.cutoff_time}</Text>
            </View>
            <Text style={styles.routeCardPrice}>from {formatPaise(route.price_from)}</Text>
          </View>
        ) : null}
      </ScrollView>
      <View style={styles.footer}>
        <Button title="Continue" onPress={onContinue} disabled={!route} />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: color.background,
  },
  content: {
    padding: space[6],
    paddingBottom: space[8],
  },
  pickerSection: {
    marginBottom: space[6],
  },
  pickerLabel: {
    ...typography.h2,
    color: color.textPrimary,
    marginBottom: space[3],
  },
  chipRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
  stationLoader: {
    marginTop: space[2],
  },
  routeLoader: {
    marginTop: space[2],
  },
  error: {
    ...typography.caption,
    color: color.error,
    marginTop: space[2],
  },
  routeCard: {
    backgroundColor: color.surface,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: color.border,
    padding: space[4],
    marginTop: space[4],
  },
  routeCardTitle: {
    ...typography.bodyStrong,
    color: color.textPrimary,
    marginBottom: space[2],
  },
  routeCardRow: {
    flexDirection: 'row',
    gap: space[4],
    marginBottom: space[2],
  },
  routeCardMeta: {
    ...typography.caption,
    color: color.textSecondary,
  },
  routeCardPrice: {
    ...typography.bodyStrong,
    color: color.primary,
  },
  footer: {
    padding: space[6],
    borderTopWidth: 1,
    borderTopColor: color.border,
    backgroundColor: color.surface,
  },
});
