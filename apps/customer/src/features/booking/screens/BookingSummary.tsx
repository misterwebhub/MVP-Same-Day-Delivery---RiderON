import React, { useEffect, useState } from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { color, radius, space, typography } from '@rideron/design-tokens';
import { PARCEL_TYPE_LABELS, WEIGHT_SLAB_LABELS, type ProhibitedItem } from '@rideron/types';
import { apiClient } from '../../../services/httpClient';
import { ApiClientError } from '@rideron/api-client';
import { Button } from '../../../components/Button';
import { Checkbox } from '../../../components/Checkbox';
import { StepProgress } from '../../../components/StepProgress';
import { formatPaise } from '../../../utils/currency';
import { formatDateLabel } from '../../../utils/date';
import { useBookingDraft } from '../BookingDraftContext';
import type { BookingStackParamList } from '../../../navigation/types';

type Props = NativeStackScreenProps<BookingStackParamList, 'BookingSummary'>;

function SummaryRow({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.row}>
      <Text style={styles.rowLabel}>{label}</Text>
      <Text style={styles.rowValue}>{value}</Text>
    </View>
  );
}

/** Review + server-quoted price + prohibited-items declaration, per docs/01's
 * "Booking Summary" step — the price quote (formerly its own PriceBreakdown
 * screen) is fetched inline here so the flow finishes in fewer taps. */
export function BookingSummary({ navigation }: Props) {
  const { draft, update } = useBookingDraft();
  const [prohibitedItems, setProhibitedItems] = useState<ProhibitedItem[]>([]);
  const [showProhibited, setShowProhibited] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [quoteLoading, setQuoteLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    apiClient.catalog
      .getProhibitedItems()
      .then((result) => setProhibitedItems(result.items))
      .catch(() => {
        // Non-critical — the declaration checkbox still works without the reference list loaded.
      });
  }, []);

  const { route, schedule, sender, receiver } = draft;

  /** Server-quoted price via POST /pricing/quote — never compute totals client-side, per docs/01. */
  useEffect(() => {
    if (!route || !schedule || !draft.weightSlab) {
      setError('Missing route or slot — please go back and select them again.');
      setQuoteLoading(false);
      return;
    }
    let cancelled = false;
    setQuoteLoading(true);
    setError(null);
    apiClient.pricing
      .getQuote({
        route_id: route.id,
        route_schedule_id: schedule.route_schedule_id,
        weight_slab: draft.weightSlab,
        quantity: draft.quantity,
        declared_value_paise: draft.declaredValuePaise,
        coupon_code: null,
      })
      .then((quote) => {
        if (!cancelled) update({ quote });
      })
      .catch((e) => {
        if (!cancelled) setError(e instanceof ApiClientError ? e.message : 'Could not get a price quote.');
      })
      .finally(() => {
        if (!cancelled) setQuoteLoading(false);
      });
    return () => {
      cancelled = true;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [route?.id, schedule?.route_schedule_id, draft.weightSlab, draft.quantity, draft.declaredValuePaise]);

  const quote = draft.quote;

  const onConfirm = async () => {
    if (!draft.prohibitedItemsAccepted) {
      setError('Please confirm your parcel doesn’t contain prohibited items.');
      return;
    }
    if (!quote || !draft.bookingDate || !draft.parcelType) {
      setError('Something’s missing from your booking — please review the previous steps.');
      return;
    }
    setSubmitting(true);
    setError(null);
    try {
      const order = await apiClient.orders.create({
        quote_token: quote.quote_token,
        booking_date: draft.bookingDate,
        sender_name: sender.name,
        sender_phone: sender.phone,
        sender_landmark: sender.landmark || null,
        receiver_name: receiver.name,
        receiver_phone: receiver.phone,
        receiver_landmark: receiver.landmark || null,
        parcel_type: draft.parcelType,
        special_instructions: draft.specialInstructions || null,
        prohibited_items_accepted: true,
      });
      if (!order.payment) {
        setError('Order created but no payment was set up — please contact support.');
        return;
      }
      navigation.navigate('Payment', { orderId: order.id, paymentId: order.payment.id });
    } catch (e) {
      setError(e instanceof ApiClientError ? e.message : 'Could not create the order. Please try again.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <View style={styles.container}>
      <StepProgress current={5} total={6} />
      <ScrollView contentContainerStyle={styles.content}>
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Route</Text>
          <SummaryRow label="From" value={route?.origin_station?.name ?? '—'} />
          <SummaryRow label="To" value={route?.destination_station?.name ?? '—'} />
          <SummaryRow label="Pickup date" value={draft.bookingDate ? formatDateLabel(draft.bookingDate) : '—'} />
          <SummaryRow label="Time slot" value={schedule ? `${schedule.departure_time} – ${schedule.arrival_time}` : '—'} />
        </View>

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Parcel</Text>
          <SummaryRow label="Type" value={draft.parcelType ? PARCEL_TYPE_LABELS[draft.parcelType] : '—'} />
          <SummaryRow label="Weight" value={draft.weightSlab ? WEIGHT_SLAB_LABELS[draft.weightSlab] : '—'} />
          <SummaryRow label="Quantity" value={String(draft.quantity)} />
          <SummaryRow label="Declared value" value={formatPaise(draft.declaredValuePaise)} />
        </View>

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Sender</Text>
          <SummaryRow label="Name" value={sender.name || '—'} />
          <SummaryRow label="Phone" value={sender.phone ? `+91 ${sender.phone}` : '—'} />
        </View>

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Receiver</Text>
          <SummaryRow label="Name" value={receiver.name || '—'} />
          <SummaryRow label="Phone" value={receiver.phone ? `+91 ${receiver.phone}` : '—'} />
        </View>

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Price</Text>
          {quoteLoading ? <ActivityIndicator color={color.primary} style={styles.quoteLoader} /> : null}
          {!quoteLoading && quote
            ? quote.breakdown.map((line) => (
                <SummaryRow
                  key={line.label}
                  label={line.label}
                  value={`${line.amount_paise < 0 ? '− ' : ''}${formatPaise(Math.abs(line.amount_paise))}`}
                />
              ))
            : null}
          <View style={[styles.row, styles.totalRow]}>
            <Text style={styles.cardTitle}>Total</Text>
            <Text style={styles.total}>{quote ? formatPaise(quote.total_amount_paise) : '—'}</Text>
          </View>
        </View>

        <Text style={styles.prohibitedLink} onPress={() => setShowProhibited((s) => !s)}>
          {showProhibited ? 'Hide' : 'View'} prohibited items list
        </Text>
        {showProhibited ? (
          <View style={styles.prohibitedList}>
            {prohibitedItems.length === 0 ? (
              <Text style={styles.prohibitedItemText}>Loading…</Text>
            ) : (
              prohibitedItems.map((item) => (
                <Text key={item.id} style={styles.prohibitedItemText}>
                  • {item.name}
                </Text>
              ))
            )}
          </View>
        ) : null}

        <View style={styles.declaration}>
          <Checkbox
            checked={draft.prohibitedItemsAccepted}
            onToggle={() => update({ prohibitedItemsAccepted: !draft.prohibitedItemsAccepted })}
            label="I confirm this parcel does not contain any prohibited or restricted items."
          />
        </View>

        {error ? <Text style={styles.error}>{error}</Text> : null}
      </ScrollView>
      <View style={styles.footer}>
        <Button
          title="Confirm & Pay"
          onPress={onConfirm}
          loading={submitting}
          disabled={!draft.prohibitedItemsAccepted || quoteLoading || !quote}
        />
      </View>
    </View>
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
  quoteLoader: {
    marginVertical: space[3],
  },
  totalRow: {
    borderTopWidth: 1,
    borderTopColor: color.border,
    marginTop: space[2],
    paddingTop: space[3],
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
  total: {
    ...typography.h2,
    color: color.primary,
  },
  prohibitedLink: {
    ...typography.bodyStrong,
    color: color.primary,
    marginBottom: space[2],
  },
  prohibitedList: {
    backgroundColor: color.surface,
    borderRadius: radius.md,
    padding: space[3],
    marginBottom: space[4],
  },
  prohibitedItemText: {
    ...typography.caption,
    color: color.textSecondary,
    marginBottom: space[1],
  },
  declaration: {
    marginTop: space[2],
    marginBottom: space[2],
  },
  error: {
    ...typography.caption,
    color: color.error,
    marginTop: space[2],
  },
  footer: {
    padding: space[6],
    borderTopWidth: 1,
    borderTopColor: color.border,
    backgroundColor: color.surface,
  },
});
