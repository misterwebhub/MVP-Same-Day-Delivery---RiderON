import React, { useEffect, useState } from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { color, radius, space, typography } from '@rideron/design-tokens';
import type { Order } from '@rideron/types';
import { apiClient } from '../../../services/httpClient';
import { ApiClientError } from '@rideron/api-client';
import { Button } from '../../../components/Button';
import { KeyboardSafeScreen } from '../../../components/KeyboardSafeScreen';
import { useSafeBottomPadding } from '../../../hooks/useSafeBottomPadding';
import { StepProgress } from '../../../components/StepProgress';
import { formatPaise } from '../../../utils/currency';
import { useBookingDraft } from '../BookingDraftContext';
import type { BookingStackParamList } from '../../../navigation/types';

type Props = NativeStackScreenProps<BookingStackParamList, 'Payment'>;

/**
 * Test-mode checkout screen. backend PAYMENT_DRIVER=mock in dev (per the
 * user's "use testing bypass payment gateway" brief) — MockPaymentGateway
 * never contacts a real processor, it just logs the call and honours a
 * `force_failure` flag (see backend/app/Services/PaymentGateway/MockPaymentGateway.php).
 * This screen calls the *real* POST /payments/{id}/verify endpoint either
 * way, so the order/payment/OTP state transitions it triggers are genuine,
 * not simulated client-side.
 */
export function Payment({ route, navigation }: Props) {
  const { orderId, paymentId } = route.params;
  const { reset } = useBookingDraft();
  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);
  const [paying, setPaying] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const bottomPadding = useSafeBottomPadding(space[6]);

  useEffect(() => {
    let cancelled = false;
    apiClient.orders
      .get(orderId)
      .then((result) => {
        if (!cancelled) setOrder(result);
      })
      .catch((e) => {
        if (!cancelled) setError(e instanceof ApiClientError ? e.message : 'Could not load this order.');
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, [orderId]);

  const pay = async (forceFailure: boolean) => {
    if (!order?.payment) return;
    setPaying(true);
    setError(null);
    try {
      const result = await apiClient.payments.verify(paymentId, {
        razorpay_order_id: order.payment.provider_order_id,
        razorpay_payment_id: `mock_pay_${Date.now()}`,
        razorpay_signature: `mock_signature_${Date.now()}`,
        force_failure: forceFailure,
      });
      if (result.payment_status === 'success') {
        reset();
        navigation.replace('Confirmation', { orderId });
      } else {
        setError('Payment was not successful. Please try again.');
      }
    } catch (e) {
      setError(e instanceof ApiClientError ? e.message : 'Payment failed. Please try again.');
    } finally {
      setPaying(false);
    }
  };

  return (
    <KeyboardSafeScreen style={styles.container}>
      <StepProgress current={6} total={6} />
      <ScrollView contentContainerStyle={styles.content}>
        {loading ? <ActivityIndicator color={color.primary} style={styles.loader} /> : null}

        {!loading && order ? (
          <View style={styles.card}>
            <Text style={styles.badge}>TEST MODE</Text>
            <Text style={styles.title}>Complete your payment</Text>
            <Text style={styles.subtitle}>Order {order.booking_reference}</Text>
            <Text style={styles.amount}>{formatPaise(order.total_amount_paise)}</Text>
            <Text style={styles.provider}>via {order.payment?.provider ?? 'mock'} gateway</Text>
          </View>
        ) : null}

        {!loading && !order ? <Text style={styles.error}>{error ?? 'Order not found.'}</Text> : null}

        {error && order ? <Text style={styles.error}>{error}</Text> : null}
      </ScrollView>
      {order ? (
        <View style={[styles.footer, { paddingBottom: bottomPadding }]}>
          <Button title={`Pay ${formatPaise(order.total_amount_paise)}`} onPress={() => pay(false)} loading={paying} />
          <Text style={styles.failureLink} onPress={() => (paying ? undefined : pay(true))}>
            Simulate a failed payment (test)
          </Text>
        </View>
      ) : null}
    </KeyboardSafeScreen>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: color.background,
  },
  content: {
    padding: space[6],
    paddingBottom: space[8],
  },
  loader: {
    marginTop: space[8],
  },
  card: {
    backgroundColor: color.surface,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: color.border,
    padding: space[6],
    alignItems: 'center',
  },
  badge: {
    ...typography.micro,
    color: color.warning,
    marginBottom: space[3],
  },
  title: {
    ...typography.h2,
    color: color.textPrimary,
    marginBottom: space[1],
  },
  subtitle: {
    ...typography.caption,
    color: color.textSecondary,
    marginBottom: space[4],
  },
  amount: {
    ...typography.display,
    color: color.primary,
    marginBottom: space[1],
  },
  provider: {
    ...typography.caption,
    color: color.textSecondary,
  },
  error: {
    ...typography.caption,
    color: color.error,
    textAlign: 'center',
    marginTop: space[4],
  },
  footer: {
    padding: space[6],
    borderTopWidth: 1,
    borderTopColor: color.border,
    backgroundColor: color.surface,
  },
  failureLink: {
    ...typography.caption,
    color: color.textSecondary,
    textAlign: 'center',
    marginTop: space[4],
  },
});
