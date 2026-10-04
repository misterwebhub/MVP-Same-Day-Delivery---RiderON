import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, RefreshControl, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { color, radius, space, typography } from '@rideron/design-tokens';
import type { ProhibitedItem } from '@rideron/types';
import { apiClient } from '../../../services/httpClient';

type Section = 'terms' | 'privacy' | 'prohibited';

const TABS: { key: Section; label: string }[] = [
  { key: 'terms', label: 'Terms' },
  { key: 'privacy', label: 'Privacy' },
  { key: 'prohibited', label: 'Prohibited' },
];

const TERMS_PARAGRAPHS = [
  'These Terms & Conditions govern your use of the RiderON app for booking station-to-station parcel delivery. By creating an account and placing a booking, you agree to these terms.',
  'Booking & payment: A booking is confirmed only after successful payment. Prices shown at checkout are quoted by our server at the time of booking and may change for future bookings based on route, weight slab, and demand.',
  'Pickup & delivery OTP: For your parcel’s safety, a one-time pickup OTP and a one-time delivery OTP are generated per order. Share the pickup OTP only with the verified rider assigned to your order, and the delivery OTP only with the intended receiver at handover. RiderON is not liable for loss caused by sharing an OTP with anyone else.',
  'Prohibited items: You must not book parcels containing any item on our Prohibited Items list (see the Prohibited tab). Booking a prohibited item may result in order cancellation without a full refund and, where applicable, reporting to authorities.',
  'Cancellations & refunds: Orders may be cancelled before pickup for a full or partial refund depending on how close to the pickup slot the cancellation is made. Once a parcel is picked up, cancellation is not possible; refunds for delivery issues are handled case-by-case through Support.',
  'Liability: RiderON facilitates delivery via independent delivery partners. Declared value at booking is used to determine any compensation for loss or damage in transit, subject to investigation.',
  'Changes to these terms: We may update these terms from time to time. Continued use of the app after an update constitutes acceptance of the revised terms.',
];

const PRIVACY_PARAGRAPHS = [
  'RiderON collects the information you provide when booking a parcel — your name, phone number, and the sender/receiver details you enter — solely to fulfil, track, and support your delivery.',
  'We share your pickup/delivery address and contact details with the delivery partner assigned to your order, and only for the duration needed to complete that delivery.',
  'Payment is processed through our payment gateway; RiderON does not store your card, UPI, or bank credentials on its own servers.',
  'We use your phone number to send OTPs and order-status notifications required to complete your booking. You can review past orders and saved addresses at any time from the Profile tab.',
  'You may request deletion of your saved addresses individually from Profile → Saved Addresses, or contact Support to request full account data deletion, subject to records we’re required to retain for completed transactions.',
];

/** Real Terms & Conditions / Privacy Policy / Prohibited Items screen — replaces the
 * ScreenStub placeholder. Terms/Privacy are static in-app copy (no backend content
 * endpoint exists yet); Prohibited Items is fetched live from GET /catalog/prohibited-items,
 * the same real endpoint BookingSummary uses, so it never drifts from what's actually enforced. */
export function Legal() {
  const [section, setSection] = useState<Section>('terms');
  const [items, setItems] = useState<ProhibitedItem[]>([]);
  const [itemsLoading, setItemsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [itemsError, setItemsError] = useState<string | null>(null);

  const load = useCallback(() => {
    setItemsError(null);
    return apiClient.catalog
      .getProhibitedItems()
      .then((result) => setItems(result.items))
      .catch(() => setItemsError('Could not load the prohibited items list right now.'));
  }, []);

  useEffect(() => {
    setItemsLoading(true);
    load().finally(() => setItemsLoading(false));
  }, [load]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await load();
    setRefreshing(false);
  }, [load]);

  return (
    <View style={styles.container}>
      <View style={styles.tabRow}>
        {TABS.map((tab) => (
          <TouchableOpacity
            key={tab.key}
            style={[styles.tab, section === tab.key && styles.tabActive]}
            onPress={() => setSection(tab.key)}
            activeOpacity={0.8}
          >
            <Text style={[styles.tabLabel, section === tab.key && styles.tabLabelActive]}>{tab.label}</Text>
          </TouchableOpacity>
        ))}
      </View>

      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={color.primary} />}
      >
        {section === 'terms' ? (
          <View>
            <Text style={styles.heading}>Terms & Conditions</Text>
            {TERMS_PARAGRAPHS.map((p, i) => (
              <Text key={i} style={styles.paragraph}>
                {p}
              </Text>
            ))}
          </View>
        ) : null}

        {section === 'privacy' ? (
          <View>
            <Text style={styles.heading}>Privacy Policy</Text>
            {PRIVACY_PARAGRAPHS.map((p, i) => (
              <Text key={i} style={styles.paragraph}>
                {p}
              </Text>
            ))}
          </View>
        ) : null}

        {section === 'prohibited' ? (
          <View>
            <Text style={styles.heading}>Prohibited Items</Text>
            <Text style={styles.paragraph}>These items cannot be booked for delivery through RiderON:</Text>
            {itemsLoading ? <ActivityIndicator color={color.primary} style={styles.loader} /> : null}
            {itemsError ? <Text style={styles.error}>{itemsError}</Text> : null}
            {!itemsLoading && !itemsError && items.length === 0 ? (
              <Text style={styles.paragraph}>No prohibited items are currently listed.</Text>
            ) : null}
            {items.map((item) => (
              <View key={item.id} style={styles.itemRow}>
                <Text style={styles.itemBullet}>•</Text>
                <Text style={styles.itemText}>{item.name}</Text>
              </View>
            ))}
          </View>
        ) : null}
      </ScrollView>
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
    padding: space[4],
    gap: space[2],
    backgroundColor: color.surface,
    borderBottomWidth: 1,
    borderBottomColor: color.border,
  },
  tab: {
    flex: 1,
    alignItems: 'center',
    paddingVertical: space[2],
    borderRadius: radius.pill,
    backgroundColor: color.background,
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
  content: {
    padding: space[5],
    paddingBottom: space[8],
  },
  heading: {
    ...typography.h2,
    color: color.textPrimary,
    marginBottom: space[3],
  },
  paragraph: {
    ...typography.body,
    color: color.textSecondary,
    marginBottom: space[3],
    lineHeight: 20,
  },
  loader: {
    marginTop: space[4],
  },
  itemRow: {
    flexDirection: 'row',
    marginBottom: space[2],
  },
  itemBullet: {
    ...typography.body,
    color: color.textSecondary,
    marginRight: space[2],
  },
  itemText: {
    ...typography.body,
    color: color.textPrimary,
    flex: 1,
  },
  error: {
    ...typography.caption,
    color: color.error,
    marginTop: space[2],
  },
});
