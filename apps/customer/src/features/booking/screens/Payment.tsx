import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { color, radius, space, typography } from '@rideron/design-tokens';
import type { Order } from '@rideron/types';
import { apiClient } from '../../../services/httpClient';
import { ApiClientError } from '@rideron/api-client';
import RazorpayCheckout, { type RazorpayErrorResult, type RazorpaySuccessResult } from 'react-native-razorpay';
import { openRazorpayCheckoutWeb } from '../../../utils/razorpayWeb';
import { Button } from '../../../components/Button';
import { KeyboardSafeScreen } from '../../../components/KeyboardSafeScreen';
import { useSafeBottomPadding } from '../../../hooks/useSafeBottomPadding';
import { StepProgress } from '../../../components/StepProgress';
import { formatPaise } from '../../../utils/currency';
import { useBookingDraft } from '../BookingDraftContext';
import type { BookingStackParamList } from '../../../navigation/types';

type Props = NativeStackScreenProps<BookingStackParamList, 'Payment'>;

/**
 * Checkout screen. Branches on `order.payment.provider` (set server-side from
 * `PAYMENT_DRIVER`, see backend/app/Http/Controllers/Api/V1/OrderController.php):
 *
 * - `razorpay`: opens the real Razorpay Checkout (native SDK via
 *   react-native-razorpay, or the hosted checkout.js overlay on web — that
 *   package has no web implementation) using the `razorpay_key_id` +
 *   `provider_order_id` the backend already created via
 *   RazorpayGateway::createOrder(). The genuine
 *   razorpay_payment_id/order_id/signature Razorpay hands back are then sent
 *   to POST /payments/{id}/verify, which HMAC-verifies them server-side
 *   (RazorpayGateway::verifySignature) before transitioning the order.
 * - `mock`: unchanged "TEST MODE" bypass buttons — MockPaymentGateway never
 *   contacts a real processor (see backend/app/Services/PaymentGateway/MockPaymentGateway.php).
 *
 * Either way this screen calls the *real* POST /payments/{id}/verify
 * endpoint, so the order/payment/OTP state transitions it triggers are
 * genuine, not simulated client-side.
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

  const isRazorpay = order?.payment?.provider === 'razorpay';

  const finish = async (result: { payment_status: string }) => {
    if (result.payment_status === 'success') {
      reset();
      navigation.replace('Confirmation', { orderId });
    } else {
      setError('Payment was not successful. Please try again.');
    }
  };

  /** Real gateway path: open Razorpay Checkout, then verify whatever it
   * actually returns — never fabricated values (see MockPaymentGateway path
   * below for the old test-only shortcut). */
  const payWithRazorpay = async () => {
    if (!order?.payment?.razorpay_key_id) return;
    setPaying(true);
    setError(null);
    try {
      const options = {
        key: order.payment.razorpay_key_id,
        amount: order.payment.amount_paise,
        currency: order.currency,
        order_id: order.payment.provider_order_id,
        name: 'RiderON',
        description: `Order ${order.booking_reference}`,
        prefill: {
          name: order.sender.name,
          contact: order.sender.phone,
        },
        theme: { color: color.primary },
      };
      const checkoutResult: RazorpaySuccessResult =
        Platform.OS === 'web' ? await openRazorpayCheckoutWeb(options) : await RazorpayCheckout.open(options);

      const result = await apiClient.payments.verify(paymentId, {
        razorpay_order_id: checkoutResult.razorpay_order_id,
        razorpay_payment_id: checkoutResult.razorpay_payment_id,
        razorpay_signature: checkoutResult.razorpay_signature,
        force_failure: false,
      });
      await finish(result);
    } catch (e) {
      if (e instanceof ApiClientError) {
        setError(e.message);
      } else {
        // Razorpay's own reject shape (both native SDK and our web shim) —
        // includes the user just closing the checkout overlay.
        const razorpayError = e as RazorpayErrorResult;
        setError(razorpayError?.error?.description ?? razorpayError?.description ?? 'Payment was cancelled or failed. Please try again.');
      }
    } finally {
      setPaying(false);
    }
  };

  /** Test-only bypass — still calls the real verify endpoint, but
   * MockPaymentGateway.verifySignature() ignores the fabricated values below
   * and just honours `force_failure`. Only reachable when
   * PAYMENT_DRIVER=mock server-side. */
  const payWithMock = async (forceFailure: boolean) => {
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
      await finish(result);
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
            {!isRazorpay ? <Text style={styles.badge}>TEST MODE</Text> : null}
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
          {isRazorpay ? (
            <Button title={`Pay ${formatPaise(order.total_amount_paise)}`} onPress={payWithRazorpay} loading={paying} />
          ) : (
            <>
              <Button title={`Pay ${formatPaise(order.total_amount_paise)}`} onPress={() => payWithMock(false)} loading={paying} />
              <Text style={styles.failureLink} onPress={() => (paying ? undefined : payWithMock(true))}>
                Simulate a failed payment (test)
              </Text>
            </>
          )}
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
