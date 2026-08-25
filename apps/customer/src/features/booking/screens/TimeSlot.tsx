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
import { formatPaise } from '../../../utils/currency';
import { toYMD, nextDays, WEEKDAYS, MONTHS } from '../../../utils/date';
import { useBookingDraft } from '../BookingDraftContext';
import type { BookingStackParamList } from '../../../navigation/types';

type Props = NativeStackScreenProps<BookingStackParamList, 'TimeSlot'>;

const DATE_OPTIONS = nextDays(7);

/** Booking date + GET /routes/{id}/schedules picker, per docs/01's "Delivery Time Slot" step. */
export function TimeSlot({ navigation }: Props) {
  const { draft, update } = useBookingDraft();
  const [bookingDate, setBookingDate] = useState<string>(draft.bookingDate ?? toYMD(DATE_OPTIONS[0]));
  const [schedules, setSchedules] = useState<RouteScheduleAvailability[]>([]);
  const [selectedSchedule, setSelectedSchedule] = useState<RouteScheduleAvailability | null>(draft.schedule);
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
        if (!cancelled) setSchedules(result);
      })
      .catch((e) => {
        if (!cancelled) setError(e instanceof ApiClientError ? e.message : 'Could not load time slots.');
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
      setError('Pick a time slot to continue.');
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

        <Text style={styles.sectionLabel}>Available slots</Text>
        {loading ? <ActivityIndicator color={color.primary} /> : null}
        {!loading && schedules.length === 0 ? (
          <Text style={styles.empty}>No slots available for this date — try another date.</Text>
        ) : null}
        {schedules.map((schedule) => {
          const selected = selectedSchedule?.route_schedule_id === schedule.route_schedule_id;
          const full = schedule.seats_available <= 0;
          return (
            <TouchableOpacity
              key={schedule.route_schedule_id}
              style={[styles.scheduleRow, selected && styles.scheduleRowSelected, full && styles.scheduleRowDisabled]}
              onPress={() => !full && setSelectedSchedule(schedule)}
              disabled={full}
            >
              <View>
                <Text style={styles.scheduleTime}>
                  {schedule.departure_time} → {schedule.arrival_time}
                </Text>
                <Text style={styles.scheduleMeta}>
                  {full ? 'Fully booked' : `${schedule.seats_available} seats left`}
                </Text>
              </View>
              <Text style={styles.schedulePrice}>from {formatPaise(schedule.price_from)}</Text>
            </TouchableOpacity>
          );
        })}

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
  empty: {
    ...typography.body,
    color: color.textSecondary,
  },
  scheduleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: color.surface,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: color.border,
    padding: space[4],
    marginBottom: space[2],
  },
  scheduleRowSelected: {
    borderColor: color.primary,
    backgroundColor: color.primaryTint,
  },
  scheduleRowDisabled: {
    opacity: 0.5,
  },
  scheduleTime: {
    ...typography.bodyStrong,
    color: color.textPrimary,
  },
  scheduleMeta: {
    ...typography.caption,
    color: color.textSecondary,
    marginTop: 2,
  },
  schedulePrice: {
    ...typography.caption,
    color: color.textSecondary,
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
