import React from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import { color, radius, space, typography } from '@rideron/design-tokens';
import type { RouteSummary } from '@rideron/types';
import { Icon } from '../../../components/Icon';
import { formatPaise } from '../../../utils/currency';

/** Resolved-route summary — distance/duration/cutoff/price at a glance, shown
 * the instant both stations are picked. Shared by Home's quick-pick card and
 * RouteSelect so a rider sees the exact same preview no matter where they
 * finished choosing their route. */
export function RoutePreviewCard({
  route,
  resolving,
  error,
}: {
  route: RouteSummary | null;
  resolving: boolean;
  error: string | null;
}) {
  if (resolving) {
    return (
      <View style={styles.statusRow}>
        <ActivityIndicator size="small" color={color.primary} />
        <Text style={styles.statusText}>Finding your route…</Text>
      </View>
    );
  }

  if (error) {
    return (
      <View style={[styles.statusRow, styles.errorRow]}>
        <Icon name="alert-circle" size={16} color={color.error} />
        <Text style={styles.errorText}>{error}</Text>
      </View>
    );
  }

  if (!route) return null;

  return (
    <View style={styles.card}>
      <View style={styles.priceRow}>
        <Text style={styles.priceLabel}>Estimated fare</Text>
        <Text style={styles.price}>{formatPaise(route.price_from)}</Text>
      </View>

      <View style={styles.statsRow}>
        <View style={styles.statItem}>
          <View style={[styles.iconBadge, { backgroundColor: color.tintBlueBg }]}>
            <Icon name="navigate-outline" size={15} color={color.tintBlueIcon} />
          </View>
          <Text style={styles.statValue}>{route.distance_km} km</Text>
          <Text style={styles.statLabel}>Distance</Text>
        </View>

        <View style={styles.divider} />

        <View style={styles.statItem}>
          <View style={[styles.iconBadge, { backgroundColor: color.tintGreenBg }]}>
            <Icon name="time-outline" size={15} color={color.tintGreenIcon} />
          </View>
          <Text style={styles.statValue}>~{route.estimated_duration_minutes} min</Text>
          <Text style={styles.statLabel}>Duration</Text>
        </View>

        <View style={styles.divider} />

        <View style={styles.statItem}>
          <View style={[styles.iconBadge, { backgroundColor: color.tintOrangeBg }]}>
            <Icon name="alarm-outline" size={15} color={color.tintOrangeIcon} />
          </View>
          <Text style={styles.statValue}>{route.cutoff_time}</Text>
          <Text style={styles.statLabel}>Cutoff</Text>
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  statusRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space[2],
    marginTop: space[2],
  },
  statusText: {
    ...typography.caption,
    color: color.textSecondary,
  },
  errorRow: {
    marginTop: space[2],
  },
  errorText: {
    ...typography.caption,
    color: color.error,
    flex: 1,
  },
  card: {
    backgroundColor: color.surface,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: color.border,
    marginTop: space[3],
    overflow: 'hidden',
  },
  priceRow: {
    flexDirection: 'row',
    alignItems: 'baseline',
    justifyContent: 'space-between',
    backgroundColor: color.secondary,
    paddingHorizontal: space[4],
    paddingVertical: space[3],
  },
  priceLabel: {
    ...typography.caption,
    color: 'rgba(255,255,255,0.7)',
  },
  price: {
    ...typography.h2,
    color: color.textInverse,
  },
  statsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: space[3],
    paddingHorizontal: space[2],
  },
  statItem: {
    flex: 1,
    alignItems: 'center',
    gap: 4,
  },
  divider: {
    width: 1,
    alignSelf: 'stretch',
    marginVertical: space[1],
    backgroundColor: color.border,
  },
  iconBadge: {
    width: 28,
    height: 28,
    borderRadius: radius.pill,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 2,
  },
  statValue: {
    ...typography.bodyStrong,
    color: color.textPrimary,
    fontSize: 13,
  },
  statLabel: {
    ...typography.caption,
    color: color.textSecondary,
    fontSize: 11,
  },
});
