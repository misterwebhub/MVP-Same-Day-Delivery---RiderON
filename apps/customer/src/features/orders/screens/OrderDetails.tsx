import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, Alert, Image, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { color, radius, space, typography } from '@rideron/design-tokens';
import type { Order, OrderStatus } from '@rideron/types';
import { ORDER_STATUS_SELF_SERVICE_CANCELLABLE } from '@rideron/types';
import { apiClient } from '../../../services/httpClient';
import { ApiClientError } from '@rideron/api-client';
import { Button } from '../../../components/Button';
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

/** Statuses where showing the Pickup/Delivery OTP resend cards is still useful —
 * i.e. before the corresponding OTP has actually been consumed. */
const SHOW_PICKUP_OTP: OrderStatus[] = ['WAITING_FOR_PICKUP', 'RIDER_ARRIVED_PICKUP', 'PICKUP_OTP_PENDING'];
const SHOW_DELIVERY_OTP: OrderStatus[] = ['ARRIVED_DESTINATION', 'WAITING_FOR_RECEIVER', 'DELIVERY_OTP_PENDING'];

function SummaryRow({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.row}>
      <Text style={styles.rowLabel}>{label}</Text>
      <Text style={styles.rowValue}>{value}</Text>
    </View>
  );
}

/**
 * Order tracking/detail screen — status timeline, route/parcel/party details,
 * OTP status cards, and self-service cancel. The API never returns rider/partner
 * info (see app/Http/Resources/OrderResource.php — no rider fields at all), so
 * this screen deliberately shows no rider name/photo/rating, only real data.
 */
export function OrderDetails({ route }: Props) {
  const { orderId } = route.params;
  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [cancelling, setCancelling] = useState(false);
  const [uploadingPhoto, setUploadingPhoto] = useState(false);

  const load = useCallback(async () => {
    try {
      const result = await apiClient.orders.get(orderId);
      setOrder(result);
      setError(null);
    } catch (e) {
      setError(e instanceof ApiClientError ? e.message : 'Could not load this order.');
    }
  }, [orderId]);

  useEffect(() => {
    setLoading(true);
    load().finally(() => setLoading(false));
  }, [load]);

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

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.content}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={color.primary} />}
    >
      <Text style={styles.reference}>{order.booking_reference}</Text>
      <Text style={styles.status}>{order.status.replace(/_/g, ' ')}</Text>

      {branch ? (
        <View style={[styles.banner, branch.tone === 'error' ? styles.bannerError : styles.bannerWarning]}>
          <Text style={styles.bannerText}>{branch.text}</Text>
          {order.cancellation_reason ? <Text style={styles.bannerReason}>{order.cancellation_reason}</Text> : null}
        </View>
      ) : (
        <View style={styles.timeline}>
          {MILESTONES.map((m) => {
            const milestoneIndex = STATUS_SEQUENCE.indexOf(m.status);
            const reached = sequenceIndex >= 0 && sequenceIndex >= milestoneIndex;
            return (
              <View key={m.status} style={styles.timelineRow}>
                <View style={[styles.timelineDot, reached && styles.timelineDotReached]} />
                <Text style={[styles.timelineLabel, reached && styles.timelineLabelReached]}>{m.label}</Text>
              </View>
            );
          })}
        </View>
      )}

      {SHOW_PICKUP_OTP.includes(order.status) ? (
        <OtpResendCard
          title="Your OTP"
          hint="Read this out to the rider when they collect the parcel."
          phone={order.sender.phone}
          orderId={order.id}
          purpose="pickup"
          otp={order.pickup_otp}
          onResent={(field) => setOrder((o) => (o ? { ...o, pickup_otp: field } : o))}
        />
      ) : null}
      {SHOW_DELIVERY_OTP.includes(order.status) ? (
        <OtpResendCard
          title="Receiver OTP"
          hint="Share this with the receiver — they give it to the rider at delivery."
          phone={order.receiver.phone}
          orderId={order.id}
          purpose="delivery"
          otp={order.delivery_otp}
          onResent={(field) => setOrder((o) => (o ? { ...o, delivery_otp: field } : o))}
          whatsappShareLabel="Share receiver OTP via WhatsApp"
        />
      ) : null}

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Route</Text>
        <SummaryRow label="From" value={order.route?.origin_station?.name ?? '—'} />
        <SummaryRow label="To" value={order.route?.destination_station?.name ?? '—'} />
        <SummaryRow label="Pickup date" value={order.booking_date ? formatDateLabel(order.booking_date) : '—'} />
        <SummaryRow
          label="Time slot"
          value={order.route_schedule ? `${order.route_schedule.departure_time} – ${order.route_schedule.arrival_time}` : '—'}
        />
      </View>

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
                <Image key={url} source={{ uri: url }} style={styles.photoThumb} />
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
  reference: {
    ...typography.h1,
    color: color.textPrimary,
  },
  status: {
    ...typography.bodyStrong,
    color: color.textSecondary,
    textTransform: 'capitalize',
    marginBottom: space[4],
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
  timeline: {
    marginBottom: space[4],
  },
  timelineRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: space[2],
  },
  timelineDot: {
    width: 10,
    height: 10,
    borderRadius: radius.pill,
    backgroundColor: color.border,
    marginRight: space[3],
  },
  timelineDotReached: {
    backgroundColor: color.primary,
  },
  timelineLabel: {
    ...typography.body,
    color: color.textSecondary,
  },
  timelineLabelReached: {
    ...typography.bodyStrong,
    color: color.textPrimary,
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
