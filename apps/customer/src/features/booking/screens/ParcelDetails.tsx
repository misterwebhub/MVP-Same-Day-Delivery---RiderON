import React, { useState } from 'react';
import { ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { color, radius, space, typography } from '@rideron/design-tokens';
import { PARCEL_TYPE_LABELS, WEIGHT_SLAB_LABELS, type ParcelType, type WeightSlab } from '@rideron/types';
import { Button } from '../../../components/Button';
import { Chip } from '../../../components/Chip';
import { StepProgress } from '../../../components/StepProgress';
import { TextField } from '../../../components/TextField';
import { useBookingDraft } from '../BookingDraftContext';
import type { BookingStackParamList } from '../../../navigation/types';

type Props = NativeStackScreenProps<BookingStackParamList, 'ParcelDetails'>;

const WEIGHT_SLABS = Object.keys(WEIGHT_SLAB_LABELS) as WeightSlab[];
const PARCEL_TYPES = Object.keys(PARCEL_TYPE_LABELS) as ParcelType[];
const MAX_QUANTITY = 10;

export function ParcelDetails({ navigation }: Props) {
  const { draft, update } = useBookingDraft();
  const [weightSlab, setWeightSlab] = useState<WeightSlab | null>(draft.weightSlab);
  const [parcelType, setParcelType] = useState<ParcelType | null>(draft.parcelType);
  const [quantity, setQuantity] = useState(draft.quantity || 1);
  const [declaredValue, setDeclaredValue] = useState(
    draft.declaredValuePaise > 0 ? String(Math.round(draft.declaredValuePaise / 100)) : '',
  );
  const [specialInstructions, setSpecialInstructions] = useState(draft.specialInstructions);
  const [error, setError] = useState<string | null>(null);

  const onContinue = () => {
    const declaredRupees = Number(declaredValue);
    if (!weightSlab) {
      setError('Choose a weight range.');
      return;
    }
    if (!parcelType) {
      setError('Choose what you’re sending.');
      return;
    }
    if (!declaredValue || Number.isNaN(declaredRupees) || declaredRupees < 0) {
      setError('Enter the declared value of your parcel.');
      return;
    }
    setError(null);
    update({
      weightSlab,
      parcelType,
      quantity,
      declaredValuePaise: Math.round(declaredRupees * 100),
      specialInstructions,
    });
    navigation.navigate('ContactDetails');
  };

  return (
    <View style={styles.container}>
      <StepProgress current={2} total={6} />
      <ScrollView contentContainerStyle={styles.content}>
        <Text style={styles.sectionLabel}>What are you sending?</Text>
        <View style={styles.chipRow}>
          {PARCEL_TYPES.map((type) => (
            <Chip key={type} label={PARCEL_TYPE_LABELS[type]} selected={parcelType === type} onPress={() => setParcelType(type)} />
          ))}
        </View>

        <Text style={styles.sectionLabel}>Approximate weight</Text>
        <View style={styles.chipRow}>
          {WEIGHT_SLABS.map((slab) => (
            <Chip key={slab} label={WEIGHT_SLAB_LABELS[slab]} selected={weightSlab === slab} onPress={() => setWeightSlab(slab)} />
          ))}
        </View>

        <Text style={styles.sectionLabel}>Quantity</Text>
        <View style={styles.stepper}>
          <TouchableOpacity
            style={styles.stepperButton}
            onPress={() => setQuantity((q) => Math.max(1, q - 1))}
            disabled={quantity <= 1}
          >
            <Text style={styles.stepperButtonText}>−</Text>
          </TouchableOpacity>
          <Text style={styles.stepperValue}>{quantity}</Text>
          <TouchableOpacity
            style={styles.stepperButton}
            onPress={() => setQuantity((q) => Math.min(MAX_QUANTITY, q + 1))}
            disabled={quantity >= MAX_QUANTITY}
          >
            <Text style={styles.stepperButtonText}>+</Text>
          </TouchableOpacity>
        </View>

        <View style={styles.field}>
          <TextField
            label="Declared value (₹)"
            value={declaredValue}
            onChangeText={(t) => setDeclaredValue(t.replace(/[^0-9]/g, ''))}
            placeholder="e.g. 500"
            keyboardType="number-pad"
            prefix="₹"
          />
        </View>

        <View style={styles.field}>
          <TextField
            label="Special instructions (optional)"
            value={specialInstructions}
            onChangeText={setSpecialInstructions}
            placeholder="Fragile, handle with care..."
            multiline
            numberOfLines={3}
            maxLength={1000}
          />
        </View>

        {error ? <Text style={styles.error}>{error}</Text> : null}
      </ScrollView>
      <View style={styles.footer}>
        <Button title="Continue" onPress={onContinue} />
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
  sectionLabel: {
    ...typography.h2,
    color: color.textPrimary,
    marginBottom: space[3],
    marginTop: space[2],
  },
  chipRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    marginBottom: space[4],
  },
  stepper: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: space[6],
  },
  stepperButton: {
    width: 40,
    height: 40,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: color.border,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: color.surface,
  },
  stepperButtonText: {
    ...typography.h1,
    color: color.textPrimary,
  },
  stepperValue: {
    ...typography.h2,
    color: color.textPrimary,
    marginHorizontal: space[5],
    minWidth: 24,
    textAlign: 'center',
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
