import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { color, radius, space, typography } from '@rideron/design-tokens';
import type { AppNotification } from '@rideron/types';
import { ApiClientError } from '@rideron/api-client';
import { apiClient } from '../../../services/httpClient';
import { Icon } from '../../../components/Icon';

/** "2026-08-12T13:05:00Z" -> "12 Aug, 1:05 PM" — manual formatting, no Intl (per date.ts convention). */
function formatTimestamp(iso: string | null): string {
  if (!iso) return '';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '';
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  let hours = d.getHours();
  const minutes = String(d.getMinutes()).padStart(2, '0');
  const ampm = hours >= 12 ? 'PM' : 'AM';
  hours = hours % 12 || 12;
  return `${d.getDate()} ${months[d.getMonth()]}, ${hours}:${minutes} ${ampm}`;
}

/** Real notifications list — GET /notifications, tap-to-mark-read via POST
 * /notifications/{id}/read. Replaces the ScreenStub placeholder. */
export function Notifications() {
  const [notifications, setNotifications] = useState<AppNotification[] | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(() => {
    return apiClient.notifications
      .list()
      .then(setNotifications)
      .catch((e) => setError(e instanceof ApiClientError ? e.message : 'Could not load notifications.'));
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

  const onPressItem = (item: AppNotification) => {
    if (item.read_at) return;
    setNotifications((prev) => (prev ?? []).map((n) => (n.id === item.id ? { ...n, read_at: new Date().toISOString() } : n)));
    apiClient.notifications.markRead(item.id).catch(() => {
      // Best-effort — if this fails the item just stays visually "unread" and will
      // sync back to server state on next pull-to-refresh.
    });
  };

  return (
    <View style={styles.container}>
      {loading ? <ActivityIndicator color={color.primary} style={styles.loader} /> : null}
      <FlatList
        data={notifications ?? []}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={styles.listContent}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={color.primary} />}
        ListEmptyComponent={
          !loading ? (
            <View style={styles.empty}>
              <Text style={styles.emptyText}>{error ?? 'No notifications yet.'}</Text>
            </View>
          ) : null
        }
        renderItem={({ item }) => (
          <TouchableOpacity
            style={[styles.row, !item.read_at && styles.rowUnread]}
            onPress={() => onPressItem(item)}
            activeOpacity={0.8}
          >
            {!item.read_at ? <View style={styles.unreadDot} /> : <View style={styles.unreadDotSpacer} />}
            <View style={styles.rowText}>
              <Text style={styles.rowTitle}>{item.title}</Text>
              <Text style={styles.rowBody}>{item.body}</Text>
              <Text style={styles.rowTime}>{formatTimestamp(item.sent_at ?? item.created_at)}</Text>
            </View>
            {!item.read_at ? <Icon name="ellipse" size={8} color={color.primary} /> : null}
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
  loader: {
    marginTop: space[8],
  },
  listContent: {
    padding: space[5],
    paddingBottom: space[8],
    flexGrow: 1,
  },
  empty: {
    alignItems: 'center',
    paddingTop: space[8],
  },
  emptyText: {
    ...typography.body,
    color: color.textSecondary,
    textAlign: 'center',
  },
  row: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    backgroundColor: color.surface,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: color.border,
    padding: space[4],
    marginBottom: space[3],
  },
  rowUnread: {
    borderColor: color.primary,
    backgroundColor: color.primaryTint,
  },
  unreadDot: {
    width: 8,
    height: 8,
    borderRadius: radius.pill,
    backgroundColor: color.primary,
    marginTop: 6,
    marginRight: space[3],
  },
  unreadDotSpacer: {
    width: 8,
    marginRight: space[3],
  },
  rowText: {
    flex: 1,
  },
  rowTitle: {
    ...typography.bodyStrong,
    color: color.textPrimary,
  },
  rowBody: {
    ...typography.caption,
    color: color.textSecondary,
    marginTop: 2,
  },
  rowTime: {
    ...typography.micro,
    color: color.textSecondary,
    marginTop: space[2],
  },
});
