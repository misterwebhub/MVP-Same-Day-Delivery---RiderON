import React, { useEffect, useState } from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { color, space, typography } from '@rideron/design-tokens';
import type { Order } from '@rideron/types';
import { apiClient } from '../../../services/httpClient';
import { Button } from '../../../components/Button';
import { OtpResendCard } from '../../orders/components/OtpResendCard';
import type { BookingStackParamList } from '../../../navigation/types';

type Props = NativeStackScreenProps<BookingStackParamList, 'Confirmation'>;

/** Booking Confirmed screen — Booking ID + Pickup/Delivery OTP status, per docs/01. The
 * actual OTP digits are never returned by the API (OtpVerification.otp_hash is $hidden,
 * see backend/app/Models/OtpVerification.php) — they only reach the customer via SMS,
 * so this screen shows delivery status + a real resend action, not a fabricated code.
 * The OTP status cards themselves live in OtpResendCard (features/orders/components) so
 * they're shared with OrderDetails, which shows the same cards while tracking. */
export function Confirmation({ route, navigation }: Props) {
  const { orderId } = route.params;
  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    apiClient.orders
      .get(orderId)
      .then(setOrder)
      .finally(() => setLoading(false));
  }, [orderId]);

  const goToTracking = () => navigation.getParent()?.navigate('OrderDetails', { orderId });
  const done = () => navigation.getParent()?.goBack();

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator color={color.primary} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <ScrollView contentContainerStyle={styles.content}>
        <Text style={styles.successEmoji}>✅</Text>
        <Text style={styles.title}>Booking confirmed</Text>
        <Text style={styles.reference}>{order?.booking_reference ?? `#${orderId}`}</Text>

        <OtpResendCard title="Pickup OTP" phone={order?.sender.phone ?? ''} orderId={orderId} purpose="pickup" />
        <OtpResendCard title="Delivery OTP" phone={order?.receiver.phone ?? ''} orderId={orderId} purpose="delivery" />
      </ScrollView>
      <View style={styles.footer}>
        <Button title="Track this order" onPress={goToTracking} />
        <Button title="Done" variant="secondary" onPress={done} style={styles.doneButton} />
      </View>
    </View>
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
    alignItems: 'center',
  },
  successEmoji: {
    fontSize: 48,
    marginBottom: space[2],
  },
  title: {
    ...typography.h1,
    color: color.textPrimary,
    marginBottom: space[1],
  },
  reference: {
    ...typography.bodyStrong,
    color: color.textSecondary,
    marginBottom: space[6],
  },
  footer: {
    padding: space[6],
    borderTopWidth: 1,
    borderTopColor: color.border,
    backgroundColor: color.surface,
  },
  doneButton: {
    marginTop: space[3],
  },
});
