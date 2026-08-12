import React, { useCallback, useEffect, useState } from 'react';
import { FlatList, RefreshControl, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import type { CompositeScreenProps } from '@react-navigation/native';
import type { BottomTabScreenProps } from '@react-navigation/bottom-tabs';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { color, radius, space, statusBadgeColor, typography } from '@rideron/design-tokens';
import type { Order, OrderStatus } from '@rideron/types';
import { apiClient } from '../../../services/httpClient';
import { formatPaise } from '../../../utils/currency';
import type { AppTabsParamList, RootStackParamList } from '../../../navigation/types';

type Props = CompositeScreenProps<BottomTabScreenProps<AppTabsParamList, 'Orders'>, NativeStackScreenProps<RootStackParamList>>;

type Tab = 'active' | 'completed' | 'cancelled';

/** backend has no status filter on GET /orders (OrderController::index just orders by id desc),
 * so these tabs are computed client-side from one fetched page. */
const COMPLETED_STATUSES: OrderStatus[] = ['COMPLETED', 'DELIVERED'];
const CANCELLED_STATUSES: OrderStatus[] = ['CANCELLED', 'REFUND_PENDING', 'REFUNDED', 'FAILED_DELIVERY', 'DISPUTED'];

function matchesTab(status: OrderStatus, tab: Tab): boolean {
  if (tab === 'completed') return COMPLETED_STATUSES.includes(status);
  if (tab === 'cancelled') return CANCELLED_STATUSES.includes(status);
  return !COMPLETED_STATUSES.includes(status) && !CANCELLED_STATUSES.includes(status);
}

const TABS: { key: Tab; label: string }[] = [
  { key: 'active', label: 'Active' },
  { key: 'completed', label: 'Completed' },
  { key: 'cancelled', label: 'Cancelled' },
];

/** Orders list — Active / Completed / Cancelled tabs, per docs/01. */
export function OrderList({ navigation }: Props) {
  const [orders, setOrders] = useState<Order[] | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [tab, setTab] = useState<Tab>('active');

  const load = useCallback(async () => {
    try {
      const result = await apiClient.orders.list({ per_page: 50 });
      setOrders(result.items);
      setError(null);
    } catch {
      setError('Could not load your orders.');
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

  const goToOrder = (orderId: number) => navigation.getParent()?.navigate('OrderDetails', { orderId });

  const filtered = (orders ?? []).filter((o) => matchesTab(o.status, tab));

  return (
    <View style={styles.container}>
      <View style={styles.tabRow}>
        {TABS.map((t) => (
          <TouchableOpacity key={t.key} style={[styles.tab, tab === t.key && styles.tabActive]} onPress={() => setTab(t.key)} activeOpacity={0.8}>
            <Text style={[styles.tabLabel, tab === t.key && styles.tabLabelActive]}>{t.label}</Text>
          </TouchableOpacity>
        ))}
      </View>

      <FlatList
        data={filtered}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={styles.listContent}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={color.primary} />}
        ListEmptyComponent={
          !loading ? (
            <View style={styles.empty}>
              <Text style={styles.emptyText}>{error ?? `No ${tab} orders yet.`}</Text>
            </View>
          ) : null
        }
        renderItem={({ item }) => (
          <TouchableOpacity style={styles.row} onPress={() => goToOrder(item.id)} activeOpacity={0.85}>
            <View style={[styles.statusDot, { backgroundColor: statusBadgeColor[item.status as keyof typeof statusBadgeColor] ?? color.info }]} />
            <View style={styles.rowText}>
              <Text style={styles.rowRef}>{item.booking_reference}</Text>
              <Text style={styles.rowRoute} numberOfLines={1}>
                {item.route?.origin_station?.name ?? '—'} → {item.route?.destination_station?.name ?? '—'}
              </Text>
              <Text style={styles.rowStatus}>{item.status.replace(/_/g, ' ')}</Text>
            </View>
            <Text style={styles.rowAmount}>{formatPaise(item.total_amount_paise)}</Text>
          </TouchableOpacity>
        )}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: color.background,
  },
  tabRow: {
    flexDirection: 'row',
    paddingHorizontal: space[6],
    paddingTop: space[6],
    paddingBottom: space[2],
  },
  tab: {
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
    textTransform: 'capitalize',
    marginTop: 2,
  },
  rowAmount: {
    ...typography.bodyStrong,
    color: color.textPrimary,
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
