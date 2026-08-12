import React, { useCallback, useEffect, useState } from 'react';
import { FlatList, RefreshControl, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import type { CompositeScreenProps } from '@react-navigation/native';
import type { BottomTabScreenProps } from '@react-navigation/bottom-tabs';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { color, radius, space, statusBadgeColor, typography } from '@rideron/design-tokens';
import type { PartnerAssignment, PartnerEarningsResponse } from '@rideron/types';
import { apiClient } from '../../../services/httpClient';
import { formatPaise } from '../../../utils/currency';
import { todayYMD } from '../../../utils/date';
import { isActiveDelivery, isPendingAccept, statusLabel } from '../../assignments/statusHelpers';
import type { AppTabsParamList, RootStackParamList } from '../../../navigation/types';

type Props = CompositeScreenProps<BottomTabScreenProps<AppTabsParamList, 'Dashboard'>, NativeStackScreenProps<RootStackParamList>>;

/** Today's assignments + earnings summary + "Active Delivery" card, per docs/06's Dashboard data section. */
export function Dashboard({ navigation }: Props) {
  const [assignments, setAssignments] = useState<PartnerAssignment[] | null>(null);
  const [earnings, setEarnings] = useState<PartnerEarningsResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    try {
      const [assignmentsResult, earningsResult] = await Promise.all([
        apiClient.partner.assignments.list({ date: todayYMD() }),
        apiClient.partner.earnings.get(),
      ]);
      setAssignments(assignmentsResult);
      setEarnings(earningsResult);
      setError(null);
    } catch {
      setError('Could not load your dashboard.');
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

  const goToAssignment = (orderId: number) => navigation.getParent()?.navigate('AssignmentDetail', { orderId });

  const list = assignments ?? [];
  const activeAssignment = list.find((a) => isActiveDelivery(a.status));
  const otherAssignments = list.filter((a) => a.id !== activeAssignment?.id);

  return (
    <FlatList
      style={styles.container}
      data={otherAssignments}
      keyExtractor={(item) => String(item.id)}
      contentContainerStyle={styles.listContent}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={color.primary} />}
      ListHeaderComponent={
        <View>
          <Text style={styles.greeting}>Today's deliveries</Text>

          {earnings ? (
            <View style={styles.earningsCard}>
              <Text style={styles.earningsLabel}>Today's earnings</Text>
              <Text style={styles.earningsAmount}>{formatPaise(earnings.today_earnings_paise)}</Text>
              <View style={styles.earningsRow}>
                <Text style={styles.earningsSub}>This week: {formatPaise(earnings.week_earnings_paise)}</Text>
                <Text style={styles.earningsSub}>This month: {formatPaise(earnings.month_earnings_paise)}</Text>
              </View>
            </View>
          ) : null}

          {activeAssignment ? (
            <TouchableOpacity
              style={styles.activeCard}
              onPress={() => goToAssignment(activeAssignment.id)}
              activeOpacity={0.85}
            >
              <Text style={styles.activeLabel}>ACTIVE DELIVERY</Text>
              <Text style={styles.activeRef}>{activeAssignment.booking_reference}</Text>
              <Text style={styles.activeRoute} numberOfLines={1}>
                {activeAssignment.route?.origin_station?.name ?? '—'} → {activeAssignment.route?.destination_station?.name ?? '—'}
              </Text>
              <Text style={styles.activeStatus}>{statusLabel(activeAssignment.status)}</Text>
            </TouchableOpacity>
          ) : null}

          {otherAssignments.length > 0 ? <Text style={styles.sectionTitle}>All assignments</Text> : null}
        </View>
      }
      ListEmptyComponent={
        !loading ? (
          <View style={styles.empty}>
            <Text style={styles.emptyText}>{error ?? 'No assignments for today yet.'}</Text>
          </View>
        ) : null
      }
      renderItem={({ item }) => (
        <TouchableOpacity style={styles.row} onPress={() => goToAssignment(item.id)} activeOpacity={0.85}>
          <View style={[styles.statusDot, { backgroundColor: statusBadgeColor[item.status as keyof typeof statusBadgeColor] ?? color.info }]} />
          <View style={styles.rowText}>
            <Text style={styles.rowRef}>{item.booking_reference}</Text>
            <Text style={styles.rowRoute} numberOfLines={1}>
              {item.route?.origin_station?.name ?? '—'} → {item.route?.destination_station?.name ?? '—'}
            </Text>
            <Text style={styles.rowStatus}>{statusLabel(item.status)}</Text>
          </View>
          {isPendingAccept(item.status) ? (
            <View style={styles.pendingBadge}>
              <Text style={styles.pendingBadgeText}>New</Text>
            </View>
          ) : null}
        </TouchableOpacity>
      )}
    />
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: color.background,
  },
  listContent: {
    padding: space[6],
    paddingTop: space[6],
    flexGrow: 1,
  },
  greeting: {
    ...typography.h1,
    color: color.textPrimary,
    marginBottom: space[4],
  },
  earningsCard: {
    backgroundColor: color.secondary,
    borderRadius: radius.lg,
    padding: space[5],
    marginBottom: space[4],
  },
  earningsLabel: {
    ...typography.caption,
    color: color.textInverse,
    opacity: 0.8,
  },
  earningsAmount: {
    ...typography.display,
    color: color.textInverse,
    marginTop: space[1],
  },
  earningsRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: space[3],
  },
  earningsSub: {
    ...typography.caption,
    color: color.textInverse,
    opacity: 0.8,
  },
  activeCard: {
    backgroundColor: color.primaryTint,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: color.primary,
    padding: space[5],
    marginBottom: space[4],
  },
  activeLabel: {
    ...typography.caption,
    color: color.primaryDark,
    fontFamily: typography.bodyStrong.fontFamily,
  },
  activeRef: {
    ...typography.h2,
    color: color.textPrimary,
    marginTop: space[2],
  },
  activeRoute: {
    ...typography.body,
    color: color.textSecondary,
    marginTop: space[1],
  },
  activeStatus: {
    ...typography.bodyStrong,
    color: color.primaryDark,
    marginTop: space[2],
  },
  sectionTitle: {
    ...typography.h2,
    color: color.textPrimary,
    marginBottom: space[3],
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: color.surface,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: color.border,
    padding: space[4],
    marginBottom: space[3],
  },
  statusDot: {
    width: 10,
    height: 10,
    borderRadius: radius.pill,
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
  rowStatus: {
    ...typography.caption,
    color: color.textSecondary,
    marginTop: 2,
  },
  pendingBadge: {
    backgroundColor: color.primary,
    borderRadius: radius.pill,
    paddingHorizontal: space[3],
    paddingVertical: space[1],
  },
  pendingBadgeText: {
    ...typography.caption,
    color: color.textInverse,
  },
  empty: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingTop: space[8],
  },
  emptyText: {
    ...typography.body,
    color: color.textSecondary,
  },
});
