import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, Alert, Linking, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { color, radius, space, statusBadgeColor, typography } from '@rideron/design-tokens';
import type { CallTarget, PartnerAssignment } from '@rideron/types';
import { ApiClientError } from '@rideron/api-client';
import { Button } from '../../../components/Button';
import { TextField } from '../../../components/TextField';
import { apiClient } from '../../../services/httpClient';
import { isPendingAccept, statusLabel } from '../statusHelpers';
import type { RootStackParamList } from '../../../navigation/types';

type Props = NativeStackScreenProps<RootStackParamList, 'AssignmentDetail'>;

/** Statuses where pickup-side actions (navigate/call sender, pickup OTP) apply. */
const PICKUP_PHASE_STATUSES = ['RIDER_ASSIGNED', 'WAITING_FOR_PICKUP', 'RIDER_ARRIVED_PICKUP', 'PICKUP_OTP_PENDING'];
const TRANSIT_STATUSES = ['PICKED_UP', 'IN_TRANSIT'];
/** Statuses where delivery-side actions (navigate/call receiver, delivery OTP) apply. */
const DELIVERY_PHASE_STATUSES = ['ARRIVED_DESTINATION', 'WAITING_FOR_RECEIVER', 'DELIVERY_OTP_PENDING'];
const DONE_STATUSES = ['DELIVERED', 'COMPLETED'];

function mapsUrl(station: { latitude: number | string; longitude: number | string } | null | undefined): string | null {
  if (!station) return null;
  return `https://www.google.com/maps/dir/?api=1&destination=${station.latitude},${station.longitude}`;
}

/**
 * Core status-driven action screen — Accept / Navigate / Call (proxy) / OTP entry
 * for pickup and delivery, per docs/06's "Assignment Detail" / "Pickup flow" /
 * "Transit" / "Delivery flow" sections.
 */
export function AssignmentDetail({ route }: Props) {
  const { orderId } = route.params;
  const [assignment, setAssignment] = useState<PartnerAssignment | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [actionLoading, setActionLoading] = useState(false);
  const [otp, setOtp] = useState('');

  const load = useCallback(async () => {
    try {
      const result = await apiClient.partner.assignments.get(orderId);
      setAssignment(result);
      setError(null);
    } catch {
      setError('Could not load this assignment.');
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

  const runAction = async (fn: () => Promise<PartnerAssignment>) => {
    setActionLoading(true);
    try {
      const result = await fn();
      setAssignment(result);
      setOtp('');
    } catch (e) {
      Alert.alert('Action failed', e instanceof ApiClientError ? e.message : 'Please try again.');
    } finally {
      setActionLoading(false);
    }
  };

  const onAccept = () => runAction(() => apiClient.partner.assignments.accept(orderId));
  const onArrivedPickup = () => runAction(() => apiClient.partner.assignments.arrivedPickup(orderId));
  const onStartTransit = () => runAction(() => apiClient.partner.assignments.startTransit(orderId));
  const onArrivedDestination = () => runAction(() => apiClient.partner.assignments.arrivedDestination(orderId));

  const onVerifyOtp = async (purpose: 'pickup' | 'delivery') => {
    if (otp.length === 0) return;
    setActionLoading(true);
    try {
      await apiClient.orders.verifyOtp(orderId, purpose, { otp });
      setOtp('');
      await load();
    } catch (e) {
      Alert.alert('Invalid OTP', e instanceof ApiClientError ? e.message : 'Please try again.');
    } finally {
      setActionLoading(false);
    }
  };

  const onCall = async (target: CallTarget) => {
    setActionLoading(true);
    try {
      await apiClient.orders.call(orderId, target);
      Alert.alert('Connecting call', `We're connecting your call to the ${target}. Your phone will ring shortly.`);
    } catch (e) {
      Alert.alert('Could not place call', e instanceof ApiClientError ? e.message : 'Please try again.');
    } finally {
      setActionLoading(false);
    }
  };

  const onNavigate = async (url: string | null) => {
    if (!url) return;
    try {
      await Linking.openURL(url);
    } catch {
      Alert.alert('Could not open maps');
    }
  };

  if (loading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator color={color.primary} />
      </View>
    );
  }

  if (!assignment) {
    return (
      <View style={styles.centered}>
        <Text style={styles.emptyText}>{error ?? 'Assignment not found.'}</Text>
      </View>
    );
  }

  const status = assignment.status;
  const pendingAccept = isPendingAccept(status);
  const pickupPhase = PICKUP_PHASE_STATUSES.includes(status);
  const transit = TRANSIT_STATUSES.includes(status);
  const deliveryPhase = DELIVERY_PHASE_STATUSES.includes(status);
  const done = DONE_STATUSES.includes(status);

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.content}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={color.primary} />}
    >
      <View style={styles.headerRow}>
        <Text style={styles.ref}>{assignment.booking_reference}</Text>
        <View style={[styles.statusPill, { backgroundColor: statusBadgeColor[status as keyof typeof statusBadgeColor] ?? color.info }]}>
          <Text style={styles.statusPillText}>{statusLabel(status)}</Text>
        </View>
      </View>

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Route</Text>
        <Text style={styles.routeText}>
          {assignment.route?.origin_station?.name ?? '—'} → {assignment.route?.destination_station?.name ?? '—'}
        </Text>
        {assignment.route_schedule ? (
          <Text style={styles.subText}>
            Departs {assignment.route_schedule.departure_time} · Arrives {assignment.route_schedule.arrival_time}
          </Text>
        ) : null}
      </View>

      {assignment.parcel ? (
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Parcel</Text>
          <Text style={styles.subText}>
            {assignment.parcel.parcel_type} · {assignment.parcel.weight_slab} · Qty {assignment.parcel.quantity}
          </Text>
          {assignment.parcel.high_value ? <Text style={styles.highValue}>High value — handle with care</Text> : null}
          {assignment.parcel.special_instructions ? (
            <Text style={styles.subText}>Note: {assignment.parcel.special_instructions}</Text>
          ) : null}
        </View>
      ) : null}

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Sender</Text>
        <Text style={styles.partyName}>{assignment.sender.name}</Text>
        <Text style={styles.subText}>{assignment.sender.phone}</Text>
        {assignment.sender.landmark ? <Text style={styles.subText}>{assignment.sender.landmark}</Text> : null}
        {(pickupPhase || pendingAccept) && !done ? (
          <View style={styles.actionRow}>
            <Button title="Navigate" variant="secondary" onPress={() => onNavigate(mapsUrl(assignment.route?.origin_station))} />
            <View style={styles.actionGap} />
            <Button title="Call Sender" variant="secondary" onPress={() => onCall('sender')} loading={actionLoading} />
          </View>
        ) : null}
      </View>

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Receiver</Text>
        <Text style={styles.partyName}>{assignment.receiver.name}</Text>
        <Text style={styles.subText}>{assignment.receiver.phone}</Text>
        {assignment.receiver.landmark ? <Text style={styles.subText}>{assignment.receiver.landmark}</Text> : null}
        {transit || deliveryPhase ? (
          <View style={styles.actionRow}>
            <Button title="Navigate" variant="secondary" onPress={() => onNavigate(mapsUrl(assignment.route?.destination_station))} />
            <View style={styles.actionGap} />
            <Button title="Call Receiver" variant="secondary" onPress={() => onCall('receiver')} loading={actionLoading} />
          </View>
        ) : null}
      </View>

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Action</Text>

        {pendingAccept ? <Button title="Accept assignment" onPress={onAccept} loading={actionLoading} /> : null}

        {pickupPhase && status !== 'RIDER_ARRIVED_PICKUP' && status !== 'PICKUP_OTP_PENDING' ? (
          <Button title="Mark arrived at pickup" onPress={onArrivedPickup} loading={actionLoading} />
        ) : null}

        {status === 'RIDER_ARRIVED_PICKUP' || status === 'PICKUP_OTP_PENDING' ? (
          <View>
            <TextField
              label="Pickup OTP"
              value={otp}
              onChangeText={setOtp}
              placeholder="Enter 4-digit OTP"
              keyboardType="number-pad"
              maxLength={6}
            />
            <View style={styles.spacer} />
            <Button title="Verify pickup OTP" onPress={() => onVerifyOtp('pickup')} loading={actionLoading} disabled={otp.length === 0} />
          </View>
        ) : null}

        {transit ? (
          <View>
            <Button title="Mark arrived at destination" onPress={onArrivedDestination} loading={actionLoading} />
            {status === 'PICKED_UP' ? (
              <>
                <View style={styles.spacer} />
                <Button title="Start transit" variant="secondary" onPress={onStartTransit} loading={actionLoading} />
              </>
            ) : null}
          </View>
        ) : null}

        {status === 'ARRIVED_DESTINATION' || status === 'WAITING_FOR_RECEIVER' || status === 'DELIVERY_OTP_PENDING' ? (
          <View>
            <TextField
              label="Delivery OTP"
              value={otp}
              onChangeText={setOtp}
              placeholder="Enter 4-digit OTP"
              keyboardType="number-pad"
              maxLength={6}
            />
            <View style={styles.spacer} />
            <Button title="Verify delivery OTP" onPress={() => onVerifyOtp('delivery')} loading={actionLoading} disabled={otp.length === 0} />
          </View>
        ) : null}

        {done ? <Text style={styles.doneText}>This delivery is {statusLabel(status).toLowerCase()}.</Text> : null}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: color.background,
  },
  content: {
    padding: space[6],
  },
  centered: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: color.background,
  },
  emptyText: {
    ...typography.body,
    color: color.textSecondary,
  },
  headerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: space[4],
  },
  ref: {
    ...typography.h1,
    color: color.textPrimary,
  },
  statusPill: {
    borderRadius: radius.pill,
    paddingHorizontal: space[3],
    paddingVertical: space[1],
  },
  statusPillText: {
    ...typography.caption,
    color: color.textInverse,
  },
  card: {
    backgroundColor: color.surface,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: color.border,
    padding: space[5],
    marginBottom: space[4],
  },
  cardTitle: {
    ...typography.caption,
    color: color.textSecondary,
    marginBottom: space[2],
  },
  routeText: {
    ...typography.bodyStrong,
    color: color.textPrimary,
  },
  subText: {
    ...typography.body,
    color: color.textSecondary,
    marginTop: space[1],
  },
  highValue: {
    ...typography.caption,
    color: color.warning,
    marginTop: space[1],
  },
  partyName: {
    ...typography.bodyStrong,
    color: color.textPrimary,
  },
  actionRow: {
    flexDirection: 'row',
    marginTop: space[4],
  },
  actionGap: {
    width: space[3],
  },
  spacer: {
    height: space[3],
  },
  doneText: {
    ...typography.body,
    color: color.success,
  },
});
