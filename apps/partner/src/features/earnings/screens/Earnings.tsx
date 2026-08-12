import React, { useCallback, useEffect, useState } from 'react';
import { FlatList, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { color, radius, space, typography } from '@rideron/design-tokens';
import type { PartnerEarningsResponse, PartnerEarningsRecentEntry } from '@rideron/types';
import { apiClient } from '../../../services/httpClient';
import { formatPaise } from '../../../utils/currency';

/** Read-only earnings summary + history — GET /partner/earnings, per docs/06's "Earnings/History" section. */
export function Earnings() {
  const [earnings, setEarnings] = useState<PartnerEarningsResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    try {
      const result = await apiClient.partner.earnings.get();
      setEarnings(result);
      setError(null);
    } catch {
      setError('Could not load your earnings.');
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

  return (
    <FlatList
      style={styles.container}
      data={earnings?.recent ?? []}
      keyExtractor={(item: PartnerEarningsRecentEntry) => String(item.order_id)}
      contentContainerStyle={styles.listContent}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={color.primary} />}
      ListHeaderComponent={
        <View>
          <Text style={styles.title}>Earnings</Text>
          {earnings ? (
            <View style={styles.summaryCard}>
              <View style={styles.summaryRow}>
                <View style={styles.summaryCell}>
                  <Text style={styles.summaryLabel}>Today</Text>
                  <Text style={styles.summaryAmount}>{formatPaise(earnings.today_earnings_paise)}</Text>
                </View>
                <View style={styles.summaryCell}>
                  <Text style={styles.summaryLabel}>This week</Text>
                  <Text style={styles.summaryAmount}>{formatPaise(earnings.week_earnings_paise)}</Text>
                </View>
                <View style={styles.summaryCell}>
                  <Text style={styles.summaryLabel}>This month</Text>
                  <Text style={styles.summaryAmount}>{formatPaise(earnings.month_earnings_paise)}</Text>
                </View>
              </View>
              <View style={styles.summaryFooter}>
                <Text style={styles.summaryFooterText}>
                  {earnings.completed_deliveries_count} completed deliveries · {earnings.commission_percent}% commission
                </Text>
              </View>
            </View>
          ) : null}
          {(earnings?.recent.length ?? 0) > 0 ? <Text style={styles.sectionTitle}>Recent</Text> : null}
        </View>
      }
      ListEmptyComponent={
        !loading ? (
          <View style={styles.empty}>
            <Text style={styles.emptyText}>{error ?? 'No completed deliveries yet.'}</Text>
          </View>
        ) : null
      }
      renderItem={({ item }) => (
        <View style={styles.row}>
          <View style={styles.rowText}>
            <Text style={styles.rowRef}>{item.booking_reference}</Text>
            <Text style={styles.rowDate}>{item.completed_at ?? '—'}</Text>
          </View>
          <Text style={styles.rowAmount}>{formatPaise(item.earnings_paise)}</Text>
        </View>
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
    flexGrow: 1,
  },
  title: {
    ...typography.h1,
    color: color.textPrimary,
    marginBottom: space[4],
  },
  summaryCard: {
    backgroundColor: color.secondary,
    borderRadius: radius.lg,
    padding: space[5],
    marginBottom: space[4],
  },
  summaryRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  summaryCell: {
    flex: 1,
  },
  summaryLabel: {
    ...typography.caption,
    color: color.textInverse,
    opacity: 0.8,
  },
  summaryAmount: {
    ...typography.h2,
    color: color.textInverse,
    marginTop: space[1],
  },
  summaryFooter: {
    marginTop: space[4],
    borderTopWidth: 1,
    borderTopColor: 'rgba(255,255,255,0.2)',
    paddingTop: space[3],
  },
  summaryFooterText: {
    ...typography.caption,
    color: color.textInverse,
    opacity: 0.8,
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
  rowText: {
    flex: 1,
  },
  rowRef: {
    ...typography.bodyStrong,
    color: color.textPrimary,
  },
  rowDate: {
    ...typography.caption,
    color: color.textSecondary,
    marginTop: 2,
  },
  rowAmount: {
    ...typography.bodyStrong,
    color: color.success,
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
