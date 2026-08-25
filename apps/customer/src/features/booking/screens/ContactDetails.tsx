import React, { useEffect, useRef, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { color, space, typography } from '@rideron/design-tokens';
import { Button } from '../../../components/Button';
import { StepProgress } from '../../../components/StepProgress';
import { TextField } from '../../../components/TextField';
import { KeyboardSafeScreen } from '../../../components/KeyboardSafeScreen';
import { useSafeBottomPadding } from '../../../hooks/useSafeBottomPadding';
import { useBookingDraft } from '../BookingDraftContext';
import { apiClient } from '../../../services/httpClient';
import type { BookingStackParamList } from '../../../navigation/types';

type Props = NativeStackScreenProps<BookingStackParamList, 'ContactDetails'>;

/** backend/app/Http/Requests/Orders/CreateOrderRequest.php: sender_phone/receiver_phone max:15, no format regex enforced server-side beyond that. */
const PHONE_REGEX = /^[6-9]\d{9}$/;

/** Sender + receiver on one screen — merged from two separate steps so booking
 * finishes in fewer taps (per the "quick booking" requirement). */
export function ContactDetails({ navigation }: Props) {
  const { draft, update } = useBookingDraft();
  const [senderName, setSenderName] = useState(draft.sender.name);
  const [senderPhone, setSenderPhone] = useState(draft.sender.phone);
  const [senderLandmark, setSenderLandmark] = useState(draft.sender.landmark);
  const [receiverName, setReceiverName] = useState(draft.receiver.name);
  const [receiverPhone, setReceiverPhone] = useState(draft.receiver.phone);
  const [receiverLandmark, setReceiverLandmark] = useState(draft.receiver.landmark);
  const [error, setError] = useState<string | null>(null);
  const bottomPadding = useSafeBottomPadding(space[6]);

  // Auto-fill sender name/mobile from the logged-in user's own profile, once,
  // only if the fields are still empty (e.g. first time through this step —
  // don't clobber anything already typed or a previously-saved draft, since
  // the sender field remains editable in case someone books on another
  // person's behalf).
  const didAutofill = useRef(false);
  useEffect(() => {
    if (didAutofill.current) return;
    if (senderName.trim().length > 0 && senderPhone.trim().length > 0) return;
    didAutofill.current = true;
    apiClient.profile
      .get()
      .then((profile) => {
        setSenderName((current) => (current.trim().length > 0 ? current : profile.name ?? current));
        setSenderPhone((current) => (current.trim().length > 0 ? current : profile.phone ?? current));
      })
      .catch(() => {
        // Silently ignore — sender fields just stay manual/blank, no worse than before.
      });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const onContinue = () => {
    if (senderName.trim().length === 0) {
      setError('Enter the sender’s name.');
      return;
    }
    if (!PHONE_REGEX.test(senderPhone)) {
      setError('Enter a valid 10-digit sender mobile number.');
      return;
    }
    if (receiverName.trim().length === 0) {
      setError('Enter the receiver’s name.');
      return;
    }
    if (!PHONE_REGEX.test(receiverPhone)) {
      setError('Enter a valid 10-digit receiver mobile number.');
      return;
    }
    setError(null);
    update({
      sender: { name: senderName.trim(), phone: senderPhone, landmark: senderLandmark.trim() },
      receiver: { name: receiverName.trim(), phone: receiverPhone, landmark: receiverLandmark.trim() },
    });
    navigation.navigate('TimeSlot');
  };

  return (
    <KeyboardSafeScreen style={styles.container}>
      <StepProgress current={3} total={6} />
      <ScrollView contentContainerStyle={styles.content}>
        <Text style={styles.sectionTitle}>Sender</Text>
        <Text style={styles.subtitle}>Who’s handing over the parcel for pickup?</Text>
        <View style={styles.field}>
          <TextField label="Full name" value={senderName} onChangeText={setSenderName} placeholder="Sender's name" autoFocus />
        </View>
        <View style={styles.field}>
          <TextField
            label="Mobile number"
            value={senderPhone}
            onChangeText={(t) => setSenderPhone(t.replace(/[^0-9]/g, ''))}
            placeholder="98765 43210"
            keyboardType="number-pad"
            maxLength={10}
            prefix="+91"
          />
        </View>
        <View style={styles.field}>
          <TextField label="Landmark (optional)" value={senderLandmark} onChangeText={setSenderLandmark} placeholder="Near..." />
        </View>

        <Text style={styles.sectionTitle}>Receiver</Text>
        <Text style={styles.subtitle}>Who’ll receive the parcel at the destination?</Text>
        <View style={styles.field}>
          <TextField label="Full name" value={receiverName} onChangeText={setReceiverName} placeholder="Receiver's name" />
        </View>
        <View style={styles.field}>
          <TextField
            label="Mobile number"
            value={receiverPhone}
            onChangeText={(t) => setReceiverPhone(t.replace(/[^0-9]/g, ''))}
            placeholder="98765 43210"
            keyboardType="number-pad"
            maxLength={10}
            prefix="+91"
          />
        </View>
        <View style={styles.field}>
          <TextField label="Landmark (optional)" value={receiverLandmark} onChangeText={setReceiverLandmark} placeholder="Near..." />
        </View>

        {error ? <Text style={styles.error}>{error}</Text> : null}
      </ScrollView>
      <View style={[styles.footer, { paddingBottom: bottomPadding }]}>
        <Button title="Continue" onPress={onContinue} />
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
  sectionTitle: {
    ...typography.h2,
    color: color.textPrimary,
    marginTop: space[2],
  },
  subtitle: {
    ...typography.caption,
    color: color.textSecondary,
    marginBottom: space[4],
    marginTop: space[1],
  },
  field: {
    marginBottom: space[4],
  },
  error: {
    ...typography.caption,
    color: color.error,
  },
  footer: {
    padding: space[6],
    borderTopWidth: 1,
    borderTopColor: color.border,
    backgroundColor: color.surface,
  },
});
