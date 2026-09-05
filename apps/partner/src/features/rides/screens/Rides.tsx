import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, AppState, Alert, FlatList, RefreshControl, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { useFocusEffect, type CompositeScreenProps } from '@react-navigation/native';
import type { BottomTabScreenProps } from '@react-navigation/bottom-tabs';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import * as Location from 'expo-location';
import { color, radius, space, statusBadgeColor, typography } from '@rideron/design-tokens';
import type { PartnerAssignment } from '@rideron/types';
import { ApiClientError } from '@rideron/api-client';
import { Button } from '../../../components/Button';
import { TextField } from '../../../components/TextField';
import { Icon } from '../../../components/Icon';
import { apiClient } from '../../../services/httpClient';
import { formatDateLabel, formatTime } from '../../../utils/date';
import { formatDistanceKm, haversineDistanceKm } from '../../../utils/distance';
import { isPendingAccept, statusLabel } from '../../assignments/statusHelpers';
import type { AppTabsParamList, RootStackParamList } from '../../../navigation/types';

/** The real (unmasked) manual pickup coordinate when the customer entered one
 * (currently Kanpur-origin orders only), else the order's fixed origin
 * station lat/lng — same fallback used for the post-accept Google Maps link
 * in AssignmentDetail. */
function pickupPoint(item: PartnerAssignment): { latitude: number; longitude: number } | null {
  const manual = item.pickup_address;
  if (manual && manual.latitude !== null && manual.longitude !== null) {
    return { latitude: manual.latitude, longitude: manual.longitude };
  }
  const station = item.route?.origin_station;
  if (!station) return null;
  return { latitude: Number(station.latitude), longitude: Number(station.longitude) };
}

type Props = CompositeScreenProps<BottomTabScreenProps<AppTabsParamList, 'Rides'>, NativeStackScreenProps<RootStackParamList>>;

type Section = 'unassigned' | 'mine';

/** Two-section Rides screen — Unassigned Rides (available to accept) / My Rides
 * (already assigned, in progress, or done) — replaces the old Dashboard per
 * the simplified-navigation request: one Rides tab instead of separate
 * Dashboard/Earnings tabs, so a rider always lands on "find work" or
 * "continue work" without extra taps. */
export function Rides({ navigation, route }: Props) {
  const [section, setSection] = useState<Section>('unassigned');

  /** A tapped "new order available" push notification navigates here with
   * { section: 'unassigned' } (see hooks/usePushNotifications.ts) — force
   * the tab even if the rider had "My Rides" open, then clear the param so
   * a later in-app tap on the "My Rides" pill isn't fought on next focus. */
  useFocusEffect(
    useCallback(() => {
      if (route.params?.section) {
        setSection(route.params.section);
        navigation.setParams({ section: undefined });
      }
    }, [route.params?.section, navigation]),
  );

  const [unassigned, setUnassigned] = useState<PartnerAssignment[] | null>(null);
  const [pendingMine, setPendingMine] = useState<PartnerAssignment[]>([]);
  const [mine, setMine] = useState<PartnerAssignment[] | null>(null);

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [acceptingId, setAcceptingId] = useState<number | null>(null);
  const [search, setSearch] = useState('');
  const [myLocation, setMyLocation] = useState<{ latitude: number; longitude: number } | null>(null);

  // Best-effort — a denied permission or unavailable GPS just means the
  // distance labels don't render, nothing in the list is blocked on this.
  useEffect(() => {
    (async () => {
      try {
        const { status } = await Location.getForegroundPermissionsAsync();
        let granted = status === 'granted';
        if (!granted) {
          const requested = await Location.requestForegroundPermissionsAsync();
          granted = requested.status === 'granted';
        }
        if (!granted) return;
        const position = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced });
        setMyLocation({ latitude: position.coords.latitude, longitude: position.coords.longitude });
      } catch {
        // Ignore — see comment above.
      }
    })();
  }, []);

  /** No date filter — a rider must see every ride they're eligible for or
   * already hold, not just today's, or a booking for tomorrow (e.g. an order
   * scheduled a day out) silently disappears from their view. */
  const load = useCallback(async () => {
    try {
      const [unassignedResult, mineResult] = await Promise.all([
        apiClient.partner.assignments.listUnassigned(),
        apiClient.partner.assignments.list(),
      ]);
      setUnassigned(unassignedResult);
      setPendingMine(mineResult.filter((a) => isPendingAccept(a.status)));
      setMine(mineResult.filter((a) => !isPendingAccept(a.status)));
      setError(null);
    } catch {
      setError('Could not load rides.');
    }
  }, []);

  useEffect(() => {
    setLoading(true);
    load().finally(() => setLoading(false));
  }, [load]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await load();
    setRefreshing(false);
  }, [load]);

  /** Same "always fresh, no manual restart" pattern as the old Dashboard:
   * refetch on focus (covers returning from AssignmentDetail after
   * processing a ride) plus a quiet background poll while foregrounded, so a
   * newly-eligible ride or a status change from another device shows up
   * without the rider doing anything. */
  useFocusEffect(
    useCallback(() => {
      load();

      const interval = setInterval(() => {
        if (AppState.currentState === 'active') load();
      }, 15000);

      return () => clearInterval(interval);
    }, [load]),
  );

  const goToAssignment = (orderId: number) => navigation.getParent()?.navigate('AssignmentDetail', { orderId });

  const onAccept = async (orderId: number) => {
    setAcceptingId(orderId);
    try {
      await apiClient.partner.assignments.accept(orderId);
      await load();
    } catch (e) {
      if (e instanceof ApiClientError && e.status === 409) {
        Alert.alert('Already taken', 'Another partner just accepted this ride.');
      } else {
        Alert.alert('Could not accept', e instanceof ApiClientError ? e.message : 'Please try again.');
      }
      await load();
    } finally {
      setAcceptingId(null);
    }
  };

  /** Trimmed, case-insensitive substring match against the booking reference,
   * same client-side approach as apps/customer's OrderList — both sections
   * are already fetched in full, so no new API call is needed just to search. */
  const query = search.trim().toUpperCase();
  const matchesQuery = (a: PartnerAssignment) => query.length === 0 || a.booking_reference.toUpperCase().includes(query);

  const unassignedList = [...(unassigned ?? []), ...pendingMine].filter(matchesQuery);
  const mineList = (mine ?? []).filter(matchesQuery);
  const data = section === 'unassigned' ? unassignedList : mineList;

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <View>
          <Text style={styles.greeting}>Ready to ride</Text>
          <Text style={styles.title}>Rides</Text>
        </View>
        <View style={styles.headerActions}>
          {/* Explicit refresh button — pull-to-refresh's drag gesture is unreliable
           * (or entirely absent) on react-native-web with mouse/trackpad input, so
           * this is the only reliable refresh path when testing in a browser. */}
          <TouchableOpacity
            accessibilityRole="button"
            accessibilityLabel="Refresh rides"
            style={styles.refreshButton}
            onPress={onRefresh}
            disabled={refreshing || loading}
            hitSlop={8}
          >
            {refreshing ? (
              <ActivityIndicator size="small" color={color.secondary} />
            ) : (
              <Icon name="refresh-outline" size={22} color={color.secondary} />
            )}
          </TouchableOpacity>
          <View style={styles.headerIconBadge}>
            <Icon name="bicycle" size={22} color={color.textInverse} />
          </View>
        </View>
      </View>

      <View style={styles.searchRow}>
        <TextField value={search} onChangeText={setSearch} placeholder="Search by order ID" />
      </View>

      <View style={styles.tabRow}>
        <TouchableOpacity
          style={[styles.tab, section === 'unassigned' && styles.tabActive]}
          onPress={() => setSection('unassigned')}
          activeOpacity={0.8}
        >
          <Icon name="albums-outline" size={15} color={section === 'unassigned' ? color.primaryDark : color.textSecondary} />
          <Text style={[styles.tabLabel, section === 'unassigned' && styles.tabLabelActive]}>
            Unassigned{unassignedList.length > 0 ? ` (${unassignedList.length})` : ''}
          </Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={[styles.tab, section === 'mine' && styles.tabActive]}
          onPress={() => setSection('mine')}
          activeOpacity={0.8}
        >
          <Icon name="checkmark-done-outline" size={15} color={section === 'mine' ? color.primaryDark : color.textSecondary} />
          <Text style={[styles.tabLabel, section === 'mine' && styles.tabLabelActive]}>My Rides</Text>
        </TouchableOpacity>
      </View>

      {loading ? (
        <View style={styles.centered}>
          <ActivityIndicator color={color.primary} />
        </View>
      ) : (
        <FlatList
          data={data}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={styles.listContent}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={color.primary} />}
          ListEmptyComponent={
            <View style={styles.empty}>
              <View style={styles.emptyIconWrap}>
                <Icon name={section === 'unassigned' ? 'search-outline' : 'cube-outline'} size={28} color={color.textSecondary} />
              </View>
              <Text style={styles.emptyText}>
                {error
                  ?? (query.length > 0
                    ? `No rides match "${search.trim()}".`
                    : section === 'unassigned'
                      ? 'No rides available right now. Pull down to refresh.'
                      : "You don't have any assigned rides yet.")}
              </Text>
            </View>
          }
          renderItem={({ item }) => {
            const statusColor = statusBadgeColor[item.status as keyof typeof statusBadgeColor] ?? color.info;
            const point = pickupPoint(item);
            const distanceLabel = myLocation && point ? formatDistanceKm(haversineDistanceKm(myLocation, point)) : null;
            return section === 'unassigned' ? (
              <View style={styles.row}>
                <View style={[styles.statusBadge, { backgroundColor: `${statusColor}1F` }]}>
                  <Icon name="cube" size={18} color={statusColor} />
                </View>
                <View style={styles.rowText}>
                  <Text style={styles.rowRef}>{item.booking_reference}</Text>
                  <Text style={styles.rowRoute} numberOfLines={1}>
                    {item.route?.origin_station?.name ?? '—'} → {item.route?.destination_station?.name ?? '—'}
                  </Text>
                  <Text style={styles.rowMeta} numberOfLines={1}>
                    {item.booking_date ? formatDateLabel(item.booking_date) : '—'}
                    {item.route_schedule ? ` • ${formatTime(item.route_schedule.departure_time)}` : ''}
                  </Text>
                  {distanceLabel ? (
                    <View style={styles.rowDistanceRow}>
                      <Icon name="navigate-outline" size={12} color={color.primaryDark} />
                      <Text style={styles.rowDistance} numberOfLines={1}>
                        {' '}
                        {distanceLabel} from pickup
                      </Text>
                    </View>
                  ) : null}
                  <Text style={[styles.rowStatus, { color: statusColor }]}>{statusLabel(item.status)}</Text>
                </View>
                <Button
                  title="Accept"
                  onPress={() => onAccept(item.id)}
                  loading={acceptingId === item.id}
                  disabled={acceptingId !== null && acceptingId !== item.id}
                />
              </View>
            ) : (
              <TouchableOpacity style={styles.row} onPress={() => goToAssignment(item.id)} activeOpacity={0.85}>
                <View style={[styles.statusBadge, { backgroundColor: `${statusColor}1F` }]}>
                  <Icon name="cube" size={18} color={statusColor} />
                </View>
                <View style={styles.rowText}>
                  <Text style={styles.rowRef}>{item.booking_reference}</Text>
                  <Text style={styles.rowRoute} numberOfLines={1}>
                    {item.route?.origin_station?.name ?? '—'} → {item.route?.destination_station?.name ?? '—'}
                  </Text>
                  <Text style={styles.rowMeta} numberOfLines={1}>
                    {item.booking_date ? formatDateLabel(item.booking_date) : '—'}
                    {item.route_schedule ? ` • ${formatTime(item.route_schedule.departure_time)}` : ''}
                  </Text>
                  <Text style={[styles.rowStatus, { color: statusColor }]}>{statusLabel(item.status)}</Text>
                </View>
                <Icon name="chevron-forward" size={18} color={color.textSecondary} />
              </TouchableOpacity>
            );
          }}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: color.background,
  },
  centered: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: space[6],
    paddingTop: space[6],
  },
  greeting: {
    ...typography.caption,
    color: color.textSecondary,
  },
  title: {
    ...typography.h1,
    color: color.textPrimary,
    marginTop: 2,
  },
  headerActions: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space[3],
  },
  refreshButton: {
    padding: space[1],
  },
  headerIconBadge: {
    width: 44,
    height: 44,
    borderRadius: radius.pill,
    backgroundColor: color.secondary,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: color.shadow,
    shadowOpacity: 0.2,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 3 },
    elevation: 4,
  },
  searchRow: {
    paddingHorizontal: space[6],
    paddingTop: space[4],
    paddingBottom: space[2],
  },
  tabRow: {
    flexDirection: 'row',
    paddingHorizontal: space[6],
    paddingTop: space[2],
    paddingBottom: space[2],
  },
  tab: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space[1],
    paddingVertical: space[2],
    paddingHorizontal: space[4],
    borderRadius: radius.pill,
    marginRight: space[2],
    backgroundColor: color.surface,
    borderWidth: 1,
    borderColor: color.border,
  },
  tabActive: {
    backgroundColor: color.primaryTint,
    borderColor: color.primary,
  },
  tabLabel: {
    ...typography.caption,
    color: color.textSecondary,
  },
  tabLabelActive: {
    ...typography.bodyStrong,
    color: color.primaryDark,
    fontSize: 13,
  },
  listContent: {
    padding: space[6],
    paddingTop: space[2],
    flexGrow: 1,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: color.surface,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: color.border,
    padding: space[4],
    marginBottom: space[3],
    shadowColor: color.shadow,
    shadowOpacity: 0.06,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 2 },
    elevation: 2,
  },
  statusBadge: {
    width: 38,
    height: 38,
    borderRadius: radius.pill,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: space[3],
  },
  rowText: {
    flex: 1,
    marginRight: space[3],
  },
  rowRef: {
    ...typography.bodyStrong,
    color: color.textPrimary,
  },
  rowRoute: {
    ...typography.caption,
    color: color.textSecondary,
    marginTop: 2,
  },
  rowMeta: {
    ...typography.caption,
    color: color.textSecondary,
    marginTop: 2,
  },
  rowDistanceRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 2,
  },
  rowDistance: {
    ...typography.caption,
    color: color.primaryDark,
    fontWeight: '700',
  },
  rowStatus: {
    ...typography.caption,
    fontWeight: '700',
    marginTop: 2,
  },
  empty: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingTop: space[8],
  },
  emptyIconWrap: {
    width: 56,
    height: 56,
    borderRadius: radius.pill,
    backgroundColor: color.surface,
    borderWidth: 1,
    borderColor: color.border,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: space[3],
  },
  emptyText: {
    ...typography.body,
    color: color.textSecondary,
    textAlign: 'center',
    paddingHorizontal: space[6],
  },
});
