import React, { useEffect, useState } from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { color, radius, space, typography } from '@rideron/design-tokens';
import type { RouteScheduleAvailability } from '@rideron/types';
import { apiClient } from '../../../services/httpClient';
import { ApiClientError } from '@rideron/api-client';
import { Button } from '../../../components/Button';
import { KeyboardSafeScreen } from '../../../components/KeyboardSafeScreen';
import { useSafeBottomPadding } from '../../../hooks/useSafeBottomPadding';
import { StepProgress } from '../../../components/StepProgress';
import { toYMD, nextDays, WEEKDAYS, MONTHS } from '../../../utils/date';
import { useBookingDraft } from '../BookingDraftContext';
import type { BookingStackParamList } from '../../../navigation/types';

type Props = NativeStackScreenProps<BookingStackParamList, 'TimeSlot'>;

const DATE_OPTIONS = nextDays(7);

/**
 * Pickup date + auto-selected departure. Per product direction, the slot
 * picker itself is removed from the customer-facing UI — we still fetch
 * real schedule/capacity data from GET /routes/{id}/schedules (required by
 * PricingEngine.quote()/OrderController.store()) and auto-pick the earliest
 * non-full departure for that date, showing only an instructional note.
 */
export function TimeSlot({ navigation }: Props) {
  const { draft, update } = useBookingDraft();
  const [bookingDate, setBookingDate] = useState<string>(draft.bookingDate ?? toYMD(DATE_OPTIONS[0]));
  const [selectedSchedule, setSelectedSchedule] = useState<RouteScheduleAvailability | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const bottomPadding = useSafeBottomPadding(space[6]);

  const routeId = draft.route?.id;

  useEffect(() => {
    if (!routeId) return;
    let cancelled = false;
    setLoading(true);
    setError(null);
    setSelectedSchedule(null);
    apiClient.catalog
      .getRouteSchedules(routeId, bookingDate)
      .then((result) => {
        if (cancelled) return;
        const earliestAvailable = result
          .filter((schedule) => schedule.seats_available > 0)
          .sort((a, b) => a.departure_time.localeCompare(b.departure_time))[0];
        setSelectedSchedule(earliestAvailable ?? null);
        if (!earliestAvailable) {
          setError('No departures available for this date — try another date.');
        }
      })
      .catch((e) => {
        if (!cancelled) setError(e instanceof ApiClientError ? e.message : 'Could not load departure options.');
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, [routeId, bookingDate]);

  const onContinue = () => {
    if (!selectedSchedule) {
      setError('No departure available for this date — try another date.');
      return;
    }
    update({ bookingDate, schedule: selectedSchedule });
    navigation.navigate('BookingSummary');
  };

  return (
    <KeyboardSafeScreen style={styles.container}>
      <StepProgress current={4} total={6} />
      <ScrollView contentContainerStyle={styles.content}>
        <Text style={styles.sectionLabel}>Pickup date</Text>
        <View style={styles.dateRow}>
          {DATE_OPTIONS.map((date) => {
            const ymd = toYMD(date);
            const selected = ymd === bookingDate;
            return (
              <TouchableOpacity
                key={ymd}
                style={[styles.dateChip, selected && styles.dateChipSelected]}
                onPress={() => setBookingDate(ymd)}
              >
                <Text style={[styles.dateChipWeekday, selected && styles.dateChipTextSelected]}>{WEEKDAYS[date.getDay()]}</Text>
                <Text style={[styles.dateChipDay, selected && styles.dateChipTextSelected]}>{date.getDate()}</Text>
                <Text style={[styles.dateChipMonth, selected && styles.dateChipTextSelected]}>{MONTHS[date.getMonth()]}</Text>
              </TouchableOpacity>
            );
          })}
        </View>

        {loading ? <ActivityIndicator color={color.primary} /> : null}
        {!loading && selectedSchedule ? (
          <Text style={styles.instruction}>
            Your parcel will be picked up on the selected date and dispatched on the next available ride — we'll notify
            you once a rider accepts.
          </Text>
        ) : null}

        {error ? <Text style={styles.error}>{error}</Text> : null}
      </ScrollView>
      <View style={[styles.footer, { paddingBottom: bottomPadding }]}>
        <Button title="Continue" onPress={onContinue} disabled={!selectedSchedule} />
      </View>
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
  sectionLabel: {
    ...typography.h2,
    color: color.textPrimary,
    marginBottom: space[3],
    marginTop: space[2],
  },
  dateRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    marginBottom: space[4],
  },
  dateChip: {
    width: 56,
    alignItems: 'center',
    paddingVertical: space[3],
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: color.border,
    backgroundColor: color.surface,
    marginRight: space[2],
    marginBottom: space[2],
  },
  dateChipSelected: {
    borderColor: color.primary,
    backgroundColor: color.primary,
  },
  dateChipWeekday: {
    ...typography.micro,
    color: color.textSecondary,
  },
  dateChipDay: {
    ...typography.h2,
    color: color.textPrimary,
  },
  dateChipMonth: {
    ...typography.caption,
    color: color.textSecondary,
  },
  dateChipTextSelected: {
    color: color.textInverse,
  },
  instruction: {
    ...typography.body,
    color: color.textSecondary,
    backgroundColor: color.surface,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: color.border,
    padding: space[4],
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
