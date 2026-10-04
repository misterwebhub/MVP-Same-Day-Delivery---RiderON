import React, { useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Alert, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import * as Location from 'expo-location';
import { color, radius, space, typography } from '@rideron/design-tokens';
import { AddressAutocompleteField } from '../../../components/AddressAutocompleteField';
import { Button } from '../../../components/Button';
import { StepProgress } from '../../../components/StepProgress';
import { TextField } from '../../../components/TextField';
import { KeyboardSafeScreen } from '../../../components/KeyboardSafeScreen';
import { useSafeBottomPadding } from '../../../hooks/useSafeBottomPadding';
import { useBookingDraft } from '../BookingDraftContext';
import { apiClient } from '../../../services/httpClient';
import { MANUAL_ADDRESS_CITIES } from '../manualAddressCities';
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

  const [pickupAddressText, setPickupAddressText] = useState(draft.pickupAddressText);
  const [pickupLatitude, setPickupLatitude] = useState(draft.pickupLatitude);
  const [pickupLongitude, setPickupLongitude] = useState(draft.pickupLongitude);
  const [locating, setLocating] = useState(false);

  const [deliveryAddressText, setDeliveryAddressText] = useState(draft.deliveryAddressText);
  const [deliveryLatitude, setDeliveryLatitude] = useState(draft.deliveryLatitude);
  const [deliveryLongitude, setDeliveryLongitude] = useState(draft.deliveryLongitude);

  // Manual address entry only applies to whichever end of the route
  // resolves to an opted-in city (currently Kanpur) — everywhere else (e.g.
  // Lucknow) keeps the existing station-only pickup/delivery, no extra UI
  // shown. Kanpur->Lucknow gets a manual pickup address; Lucknow->Kanpur
  // gets a manual delivery address instead.
  const isManualPickupCity = MANUAL_ADDRESS_CITIES.includes((draft.originCity?.name ?? '').trim().toLowerCase());
  const isManualDeliveryCity = MANUAL_ADDRESS_CITIES.includes((draft.destinationCity?.name ?? '').trim().toLowerCase());

  const onUseCurrentLocation = async () => {
    setLocating(true);
    try {
      const { status } = await Location.getForegroundPermissionsAsync();
      let granted = status === 'granted';
      if (!granted) {
        const requested = await Location.requestForegroundPermissionsAsync();
        granted = requested.status === 'granted';
      }
      if (!granted) {
        Alert.alert('Location permission needed', 'Allow location access so the rider can find your pickup point.');
        return;
      }
      const position = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced });
      setPickupLatitude(position.coords.latitude);
      setPickupLongitude(position.coords.longitude);
    } catch {
      Alert.alert('Could not get location', 'Please try again, or just describe the address in words below.');
    } finally {
      setLocating(false);
    }
  };

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
      pickupAddressText: isManualPickupCity ? pickupAddressText.trim() : '',
      pickupLatitude: isManualPickupCity ? pickupLatitude : null,
      pickupLongitude: isManualPickupCity ? pickupLongitude : null,
      deliveryAddressText: isManualDeliveryCity ? deliveryAddressText.trim() : '',
      deliveryLatitude: isManualDeliveryCity ? deliveryLatitude : null,
      deliveryLongitude: isManualDeliveryCity ? deliveryLongitude : null,
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

        {isManualPickupCity ? (
          <View style={styles.pickupCard}>
            <Text style={styles.pickupTitle}>Pickup address</Text>
            <Text style={styles.subtitle}>
              Enter where the rider should pick up the parcel from — search for it or describe the address, and share your current location.
            </Text>
            <View style={styles.field}>
              <AddressAutocompleteField
                label="Address"
                value={pickupAddressText}
                onChangeText={setPickupAddressText}
                onCoordinateResolved={(latitude, longitude) => {
                  setPickupLatitude(latitude);
                  setPickupLongitude(longitude);
                }}
                placeholder="Search or type House / street / area"
                multiline
              />
            </View>
            <Button
              title={pickupLatitude !== null ? 'Location captured ✓ · Update' : 'Use my current location'}
              variant="secondary"
              onPress={onUseCurrentLocation}
              loading={locating}
            />
          </View>
        ) : null}

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

        {isManualDeliveryCity ? (
          <View style={styles.pickupCard}>
            <Text style={styles.pickupTitle}>Delivery address</Text>
            <Text style={styles.subtitle}>
              Search for where the parcel should be delivered — if search isn’t available, just type the address; the rider will still get it.
            </Text>
            <View style={styles.field}>
              <AddressAutocompleteField
                label="Address"
                value={deliveryAddressText}
                onChangeText={setDeliveryAddressText}
                onCoordinateResolved={(latitude, longitude) => {
                  setDeliveryLatitude(latitude);
                  setDeliveryLongitude(longitude);
                }}
                placeholder="Search or type House / street / area"
                multiline
              />
            </View>
          </View>
        ) : null}

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
  pickupCard: {
    backgroundColor: color.surface,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: color.border,
    padding: space[4],
    marginBottom: space[5],
  },
  pickupTitle: {
    ...typography.bodyStrong,
    color: color.textPrimary,
    marginBottom: space[1],
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
