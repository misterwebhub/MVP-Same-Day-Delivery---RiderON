import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, AppState, Image, Linking, RefreshControl, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import { useFocusEffect } from '@react-navigation/native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { color, radius, space, statusBadgeColor, typography, type StatusBadgeKey } from '@rideron/design-tokens';
import type { Order, OrderStatus } from '@rideron/types';
import { ORDER_STATUS_SELF_SERVICE_CANCELLABLE } from '@rideron/types';
import { apiClient } from '../../../services/httpClient';
import { ApiClientError } from '@rideron/api-client';
import { Button } from '../../../components/Button';
import { Icon } from '../../../components/Icon';
import { ImageViewerModal } from '../../../components/ImageViewerModal';
import { resetToHome } from '../../../components/HomeButton';
import { formatPaise } from '../../../utils/currency';
import { formatDateLabel } from '../../../utils/date';
import { OtpResendCard } from '../components/OtpResendCard';
import type { RootStackParamList } from '../../../navigation/types';

type Props = NativeStackScreenProps<RootStackParamList, 'OrderDetails'>;

/** Happy-path lifecycle order, per app/Constants/OrderStatus.php — used to compute
 * which milestones have been "reached" via indexOf comparison. Branch/terminal
 * statuses outside this path (cancelled/refunded/failed/disputed) are handled
 * separately with a banner instead of the checklist. */
const STATUS_SEQUENCE: OrderStatus[] = [
  'PAYMENT_PENDING',
  'BOOKED',
  'RIDER_ASSIGNMENT_PENDING',
  'RIDER_ASSIGNED',
  'WAITING_FOR_PICKUP',
  'RIDER_ARRIVED_PICKUP',
  'PICKUP_OTP_PENDING',
  'PICKED_UP',
  'IN_TRANSIT',
  'ARRIVED_DESTINATION',
  'WAITING_FOR_RECEIVER',
  'DELIVERY_OTP_PENDING',
  'DELIVERED',
  'COMPLETED',
];

const MILESTONES: { status: OrderStatus; label: string }[] = [
  { status: 'BOOKED', label: 'Booked' },
  { status: 'RIDER_ASSIGNED', label: 'Rider assigned' },
  { status: 'PICKED_UP', label: 'Picked up' },
  { status: 'IN_TRANSIT', label: 'In transit' },
  { status: 'DELIVERED', label: 'Delivered' },
  { status: 'COMPLETED', label: 'Completed' },
];

const BRANCH_STATUS_TONE: Partial<Record<OrderStatus, { tone: 'warning' | 'error'; text: string }>> = {
  PAYMENT_FAILED: { tone: 'error', text: 'Payment failed for this order.' },
  CANCELLED: { tone: 'error', text: 'This order was cancelled.' },
  REFUND_PENDING: { tone: 'warning', text: 'A refund is being processed for this order.' },
  REFUNDED: { tone: 'warning', text: 'This order was refunded.' },
  FAILED_DELIVERY: { tone: 'error', text: 'Delivery could not be completed.' },
  DISPUTED: { tone: 'warning', text: 'This order is under dispute review.' },
};

/** Statuses where showing the Pickup/Delivery OTP cards is still useful — i.e.
 * from the moment the order is booked (the backend generates both codes at
 * booking time, so both are already valid then — same as what Confirmation
 * shows right after booking) through the whole tracking lifecycle up until
 * delivery. Both cards show together throughout — a customer should be able
 * to see (and share) the receiver's code well before pickup even happens, not
 * just once the parcel is already in transit. OtpResendCard itself handles
 * the "verified"/"expired" states once a code is actually consumed, so there's
 * no need to hide the card the moment its status changes underneath it. */
const BOOKED_INDEX = STATUS_SEQUENCE.indexOf('BOOKED');
const DELIVERED_INDEX = STATUS_SEQUENCE.indexOf('DELIVERED');
function showOtpCards(sequenceIndex: number): boolean {
  return sequenceIndex >= BOOKED_INDEX && sequenceIndex < DELIVERED_INDEX;
}

function SummaryRow({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.row}>
      <Text style={styles.rowLabel}>{label}</Text>
      <Text style={styles.rowValue}>{value}</Text>
    </View>
  );
}

/** Stacked (label above value) for the manual pickup/delivery address, which
 * can run to a full sentence and would get squeezed against SummaryRow's
 * right edge otherwise. */
function SummaryBlock({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.block}>
      <Text style={styles.rowLabel}>{label}</Text>
      <Text style={styles.blockValue}>{value}</Text>
    </View>
  );
}

/**
 * Order tracking/detail screen — status timeline, route/parcel/party details,
 * OTP status cards, and self-service cancel. Once a partner accepts (no more
 * silent auto-assign — see PartnerAssignmentService), `order.partner` is
 * populated and a Rider card shows their name/phone/vehicle + a tap-to-call
 * button; before that it's null and the card is simply omitted.
 */
export function OrderDetails({ route, navigation }: Props) {
  const { orderId } = route.params;
  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [cancelling, setCancelling] = useState(false);
  const [uploadingPhoto, setUploadingPhoto] = useState(false);
  const [previewUri, setPreviewUri] = useState<string | null>(null);

  // Tracks the previously-seen status so the "just completed" Alert below only
  // fires once, on the transition into COMPLETED — not every poll/refresh
  // while an already-completed order is being reviewed from Orders history.
  const previousStatusRef = useRef<OrderStatus | null>(null);
  const announcedCompletionRef = useRef(false);

  const load = useCallback(async () => {
    try {
      const result = await apiClient.orders.get(orderId);
      setOrder(result);
      setError(null);

      const previousStatus = previousStatusRef.current;
      if (previousStatus !== null && previousStatus !== 'COMPLETED' && result.status === 'COMPLETED' && !announcedCompletionRef.current) {
        announcedCompletionRef.current = true;
        Alert.alert('Delivery completed!', 'Your parcel has been delivered successfully.', [
          { text: 'Go to Home', onPress: () => resetToHome(navigation) },
        ]);
      }
      previousStatusRef.current = result.status;
    } catch (e) {
      setError(e instanceof ApiClientError ? e.message : 'Could not load this order.');
    }
  }, [orderId, navigation]);

  useEffect(() => {
    setLoading(true);
    load().finally(() => setLoading(false));
  }, [load]);

  // Live status while this screen is open — a rider marking the order
  // delivered/completed on their device should reach the customer here
  // without requiring a manual pull-to-refresh first.
  useFocusEffect(
    useCallback(() => {
      const interval = setInterval(() => {
        if (AppState.currentState === 'active') load();
      }, 15000);
      return () => clearInterval(interval);
    }, [load]),
  );

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await load();
    setRefreshing(false);
  }, [load]);

  const confirmCancel = () => {
    Alert.alert('Cancel this order?', 'This cannot be undone.', [
      { text: 'Keep order', style: 'cancel' },
      { text: 'Cancel order', style: 'destructive', onPress: doCancel },
    ]);
  };

  const doCancel = async () => {
    setCancelling(true);
    try {
      const updated = await apiClient.orders.cancel(orderId, {});
      setOrder(updated);
    } catch (e) {
      Alert.alert('Could not cancel', e instanceof ApiClientError ? e.message : 'Please try again.');
    } finally {
      setCancelling(false);
    }
  };

  const onAddParcelPhoto = async () => {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      Alert.alert('Photo permission needed', 'Allow photo library access to attach a parcel photo.');
      return;
    }
    const result = await ImagePicker.launchImageLibraryAsync({ quality: 0.6, mediaTypes: ImagePicker.MediaTypeOptions.Images });
    if (result.canceled || !result.assets?.[0]) return;

    const asset = result.assets[0];
    setUploadingPhoto(true);
    try {
      const updated = await apiClient.orders.uploadParcelPhoto(orderId, {
        uri: asset.uri,
        name: 'parcel-photo.jpg',
        type: 'image/jpeg',
      });
      setOrder(updated);
    } catch (e) {
      Alert.alert('Upload failed', e instanceof ApiClientError ? e.message : 'Please try again.');
    } finally {
      setUploadingPhoto(false);
    }
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator color={color.primary} />
      </View>
    );
  }

  if (!order) {
    return (
      <View style={styles.loadingContainer}>
        <Text style={styles.errorText}>{error ?? 'Order not found.'}</Text>
      </View>
    );
  }

  const branch = BRANCH_STATUS_TONE[order.status];
  const sequenceIndex = STATUS_SEQUENCE.indexOf(order.status);
  const cancellable = ORDER_STATUS_SELF_SERVICE_CANCELLABLE.includes(order.status);
  const statusPillColor = statusBadgeColor[order.status as StatusBadgeKey] ?? color.textSecondary;

  return (
    <>
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.content}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={color.primary} />}
    >
      <View style={styles.headerRow}>
        <Text style={styles.reference}>{order.booking_reference}</Text>
        <View style={[styles.statusPill, { backgroundColor: `${statusPillColor}22` }]}>
          <View style={[styles.statusPillDot, { backgroundColor: statusPillColor }]} />
          <Text style={[styles.statusPillText, { color: statusPillColor }]}>{order.status.replace(/_/g, ' ')}</Text>
        </View>
      </View>

      {branch ? (
        <View style={[styles.banner, branch.tone === 'error' ? styles.bannerError : styles.bannerWarning]}>
          <Text style={styles.bannerText}>{branch.text}</Text>
          {order.cancellation_reason ? <Text style={styles.bannerReason}>{order.cancellation_reason}</Text> : null}
        </View>
      ) : (
        <View style={styles.timelineCard}>
          {MILESTONES.map((m, idx) => {
            const milestoneIndex = STATUS_SEQUENCE.indexOf(m.status);
            const reached = sequenceIndex >= 0 && sequenceIndex >= milestoneIndex;
            const nextMilestoneIndex = idx < MILESTONES.length - 1 ? STATUS_SEQUENCE.indexOf(MILESTONES[idx + 1].status) : Infinity;
            const isCurrent = reached && sequenceIndex < nextMilestoneIndex;
            const isLast = idx === MILESTONES.length - 1;
            return (
              <View key={m.status} style={styles.timelineRow}>
                <View style={styles.timelineIndicator}>
                  <View
                    style={[
                      styles.timelineDot,
                      reached && styles.timelineDotReached,
                      isCurrent && styles.timelineDotCurrent,
                    ]}
                  >
                    {reached && !isCurrent ? <Icon name="checkmark" size={11} color={color.textInverse} /> : null}
                  </View>
                  {!isLast ? <View style={[styles.timelineConnector, reached && styles.timelineConnectorReached]} /> : null}
                </View>
                <View style={styles.timelineTextWrap}>
                  <Text style={[styles.timelineLabel, reached && styles.timelineLabelReached, isCurrent && styles.timelineLabelCurrent]}>
                    {m.label}
                  </Text>
                  {isCurrent ? <Text style={styles.timelineCurrentTag}>Current status</Text> : null}
                </View>
              </View>
            );
          })}
        </View>
      )}

      {showOtpCards(sequenceIndex) ? (
        <OtpResendCard
          title="Your OTP"
          hint="Read this out to the rider when they collect the parcel."
          phone={order.sender.phone}
          purpose="pickup"
          otp={order.pickup_otp}
        />
      ) : null}
      {showOtpCards(sequenceIndex) ? (
        <OtpResendCard
          title="Receiver OTP"
          hint="Share this with the receiver — they give it to the rider at delivery."
          phone={order.receiver.phone}
          purpose="delivery"
          otp={order.delivery_otp}
          whatsappShareLabel="Share receiver OTP via WhatsApp"
        />
      ) : null}

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Route</Text>
        <SummaryRow label="From" value={order.route?.origin_station?.name ?? '—'} />
        {order.pickup_address?.text ? (
          <SummaryBlock
            label="Pickup address"
            value={
              order.pickup_address.postal_code
                ? `${order.pickup_address.text} — ${order.pickup_address.postal_code}`
                : order.pickup_address.text
            }
          />
        ) : null}
        <SummaryRow label="To" value={order.route?.destination_station?.name ?? '—'} />
        {order.delivery_address?.text ? (
          <SummaryBlock
            label="Delivery address"
            value={
              order.delivery_address.postal_code
                ? `${order.delivery_address.text} — ${order.delivery_address.postal_code}`
                : order.delivery_address.text
            }
          />
        ) : null}
        <SummaryRow label="Pickup date" value={order.booking_date ? formatDateLabel(order.booking_date) : '—'} />
        <SummaryRow
          label="Time slot"
          value={order.route_schedule ? `${order.route_schedule.departure_time} – ${order.route_schedule.arrival_time}` : '—'}
        />
      </View>

      {order.partner ? (
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Your rider</Text>
          <SummaryRow label="Name" value={order.partner.name ?? '—'} />
          {order.partner.vehicle_type ? <SummaryRow label="Vehicle" value={order.partner.vehicle_type.replace(/_/g, ' ')} /> : null}
          {order.partner.rating_avg !== null ? <SummaryRow label="Rating" value={`${order.partner.rating_avg.toFixed(1)} ★`} /> : null}
          {order.partner.phone ? (
            <Button
              title={`Call ${order.partner.name ?? 'rider'}`}
              variant="secondary"
              onPress={() => Linking.openURL(`tel:${order.partner!.phone}`)}
              style={styles.addPhotoButton}
            />
          ) : null}
        </View>
      ) : null}

      {order.parcel ? (
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Parcel</Text>
          <SummaryRow label="Type" value={order.parcel.parcel_type} />
          <SummaryRow label="Weight" value={order.parcel.weight_slab} />
          <SummaryRow label="Quantity" value={String(order.parcel.quantity)} />
          <SummaryRow label="Declared value" value={formatPaise(order.parcel.declared_value_paise)} />
          {order.parcel.special_instructions ? <SummaryRow label="Notes" value={order.parcel.special_instructions} /> : null}

          {order.parcel.photos.length > 0 ? (
            <View style={styles.photoRow}>
              {order.parcel.photos.map((url) => (
                <TouchableOpacity
                  key={url}
                  activeOpacity={0.85}
                  onPress={() => setPreviewUri(url)}
                  accessibilityRole="button"
                  accessibilityLabel="View parcel photo full size"
                >
                  <Image source={{ uri: url }} style={styles.photoThumb} />
                </TouchableOpacity>
              ))}
            </View>
          ) : null}
          {sequenceIndex >= 0 && sequenceIndex < STATUS_SEQUENCE.indexOf('PICKED_UP') ? (
            <Button
              title={uploadingPhoto ? 'Uploading…' : 'Add parcel photo'}
              variant="secondary"
              onPress={onAddParcelPhoto}
              loading={uploadingPhoto}
              style={styles.addPhotoButton}
            />
          ) : null}
        </View>
      ) : null}

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Sender</Text>
        <SummaryRow label="Name" value={order.sender.name} />
        <SummaryRow label="Phone" value={`+91 ${order.sender.phone}`} />
        {order.sender.landmark ? <SummaryRow label="Landmark" value={order.sender.landmark} /> : null}
      </View>

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Receiver</Text>
        <SummaryRow label="Name" value={order.receiver.name} />
        <SummaryRow label="Phone" value={`+91 ${order.receiver.phone}`} />
        {order.receiver.landmark ? <SummaryRow label="Landmark" value={order.receiver.landmark} /> : null}
      </View>

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Price</Text>
        {order.price_breakdown.map((line) => (
          <SummaryRow key={line.label} label={line.label} value={formatPaise(line.amount_paise)} />
        ))}
        <View style={styles.divider} />
        <View style={styles.row}>
          <Text style={styles.totalLabel}>Total</Text>
          <Text style={styles.totalValue}>{formatPaise(order.total_amount_paise)}</Text>
        </View>
      </View>

      {error ? <Text style={styles.errorText}>{error}</Text> : null}

      {cancellable ? (
        <Button title="Cancel order" variant="secondary" onPress={confirmCancel} loading={cancelling} style={styles.cancelButton} />
      ) : null}
    </ScrollView>
    <ImageViewerModal uri={previewUri} onClose={() => setPreviewUri(null)} />
    </>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: color.background,
  },
  loadingContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: color.background,
  },
  content: {
    padding: space[6],
    paddingBottom: space[8],
  },
  headerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: space[4],
    flexWrap: 'wrap',
    gap: space[2],
  },
  reference: {
    ...typography.h1,
    color: color.textPrimary,
  },
  statusPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space[1],
    borderRadius: radius.pill,
    paddingHorizontal: space[3],
    paddingVertical: space[1],
  },
  statusPillDot: {
    width: 7,
    height: 7,
    borderRadius: radius.pill,
  },
  statusPillText: {
    ...typography.caption,
    fontWeight: '700',
    textTransform: 'capitalize',
  },
  banner: {
    borderRadius: radius.md,
    padding: space[4],
    marginBottom: space[4],
  },
  bannerError: {
    backgroundColor: '#FDECEC',
  },
  bannerWarning: {
    backgroundColor: '#FFF6E5',
  },
  bannerText: {
    ...typography.bodyStrong,
    color: color.textPrimary,
  },
  bannerReason: {
    ...typography.caption,
    color: color.textSecondary,
    marginTop: space[1],
  },
  timelineCard: {
    backgroundColor: color.surface,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: color.border,
    padding: space[4],
    paddingBottom: space[1],
    marginBottom: space[4],
  },
  timelineRow: {
    flexDirection: 'row',
  },
  timelineIndicator: {
    alignItems: 'center',
    width: 24,
  },
  timelineDot: {
    width: 20,
    height: 20,
    borderRadius: radius.pill,
    backgroundColor: color.background,
    borderWidth: 2,
    borderColor: color.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  timelineDotReached: {
    backgroundColor: color.primary,
    borderColor: color.primary,
  },
  timelineDotCurrent: {
    backgroundColor: color.surface,
    borderColor: color.primary,
    borderWidth: 3,
  },
  timelineConnector: {
    width: 2,
    flex: 1,
    minHeight: space[6],
    backgroundColor: color.border,
    marginVertical: 2,
  },
  timelineConnectorReached: {
    backgroundColor: color.primary,
  },
  timelineTextWrap: {
    flex: 1,
    paddingLeft: space[3],
    paddingBottom: space[4],
  },
  timelineLabel: {
    ...typography.body,
    color: color.textSecondary,
  },
  timelineLabelReached: {
    ...typography.bodyStrong,
    color: color.textPrimary,
  },
  timelineLabelCurrent: {
    color: color.primary,
  },
  timelineCurrentTag: {
    ...typography.caption,
    color: color.primary,
    marginTop: 2,
  },
  card: {
    backgroundColor: color.surface,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: color.border,
    padding: space[4],
    marginBottom: space[4],
  },
  cardTitle: {
    ...typography.h2,
    color: color.textPrimary,
    marginBottom: space[2],
  },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: space[1],
  },
  rowLabel: {
    ...typography.caption,
    color: color.textSecondary,
  },
  rowValue: {
    ...typography.body,
    color: color.textPrimary,
  },
  block: {
    paddingVertical: space[1],
    paddingLeft: space[2],
  },
  blockValue: {
    ...typography.body,
    color: color.textPrimary,
    marginTop: 2,
  },
  divider: {
    height: 1,
    backgroundColor: color.border,
    marginVertical: space[2],
  },
  totalLabel: {
    ...typography.h2,
    color: color.textPrimary,
  },
  totalValue: {
    ...typography.h2,
    color: color.primary,
  },
  errorText: {
    ...typography.caption,
    color: color.error,
    textAlign: 'center',
    marginBottom: space[3],
  },
  cancelButton: {
    marginTop: space[2],
  },
  photoRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    marginTop: space[3],
    gap: space[2],
  },
  photoThumb: {
    width: 64,
    height: 64,
    borderRadius: radius.md,
    backgroundColor: color.border,
  },
  addPhotoButton: {
    marginTop: space[3],
  },
});
