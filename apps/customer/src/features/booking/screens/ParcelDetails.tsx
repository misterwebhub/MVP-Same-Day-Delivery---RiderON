import React, { useState } from 'react';
import { Image, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { color, radius, space, typography } from '@rideron/design-tokens';
import { PARCEL_TYPE_LABELS, WEIGHT_SLAB_LABELS, type ParcelType, type WeightSlab } from '@rideron/types';
import { Button } from '../../../components/Button';
import { Chip } from '../../../components/Chip';
import { Icon } from '../../../components/Icon';
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
  const [photoUri, setPhotoUri] = useState<string | null>(draft.parcelPhotoUri);
  const [error, setError] = useState<string | null>(null);
  const [pickerVisible, setPickerVisible] = useState(false);

  /** A photo is required — it's what the rider matches against at pickup and
   * gives support a real reference if a parcel is ever disputed. Offer both
   * camera and library. Uses an in-app chooser rather than the native
   * Alert.alert action-sheet API, since react-native-web doesn't implement
   * Alert at all — Alert.alert() silently no-ops on web, which made "Add
   * photo" appear completely broken there. */
  const onAddPhoto = () => {
    setPickerVisible(true);
  };

  const captureFromCamera = async () => {
    setPickerVisible(false);
    const permission = await ImagePicker.requestCameraPermissionsAsync();
    if (!permission.granted) {
      setError('Camera permission needed — allow camera access to photograph your parcel.');
      return;
    }
    const result = await ImagePicker.launchCameraAsync({ quality: 0.6 });
    if (result.canceled || !result.assets?.[0]) return;
    setPhotoUri(result.assets[0].uri);
    setError(null);
  };

  const captureFromLibrary = async () => {
    setPickerVisible(false);
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      setError('Photo permission needed — allow photo library access to attach a parcel photo.');
      return;
    }
    const result = await ImagePicker.launchImageLibraryAsync({ quality: 0.6, mediaTypes: ImagePicker.MediaTypeOptions.Images });
    if (result.canceled || !result.assets?.[0]) return;
    setPhotoUri(result.assets[0].uri);
    setError(null);
  };

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
    if (!photoUri) {
      setError('Add a photo of your parcel — it’s required for pickup verification.');
      return;
    }
    setError(null);
    update({
      weightSlab,
      parcelType,
      quantity,
      declaredValuePaise: Math.round(declaredRupees * 100),
      specialInstructions,
      parcelPhotoUri: photoUri,
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

        <Text style={styles.sectionLabel}>Photo of your parcel</Text>
        <Text style={styles.photoHint}>Required — helps our rider confirm the right parcel at pickup.</Text>
        {photoUri ? (
          <TouchableOpacity style={styles.photoPreviewWrap} activeOpacity={0.85} onPress={onAddPhoto}>
            <Image source={{ uri: photoUri }} style={styles.photoPreview} />
            <View style={styles.photoRetakeBadge}>
              <Icon name="camera-outline" size={14} color={color.textInverse} />
              <Text style={styles.photoRetakeText}>Retake</Text>
            </View>
          </TouchableOpacity>
        ) : (
          <TouchableOpacity style={styles.photoAddBox} activeOpacity={0.85} onPress={onAddPhoto}>
            <Icon name="camera-outline" size={22} color={color.primary} />
            <Text style={styles.photoAddText}>Add photo</Text>
          </TouchableOpacity>
        )}

        {pickerVisible ? (
          <View style={styles.photoPickerCard}>
            <TouchableOpacity style={styles.photoPickerOption} activeOpacity={0.7} onPress={captureFromCamera}>
              <Icon name="camera-outline" size={18} color={color.textPrimary} />
              <Text style={styles.photoPickerOptionText}>Take photo</Text>
            </TouchableOpacity>
            <View style={styles.photoPickerDivider} />
            <TouchableOpacity style={styles.photoPickerOption} activeOpacity={0.7} onPress={captureFromLibrary}>
              <Icon name="images-outline" size={18} color={color.textPrimary} />
              <Text style={styles.photoPickerOptionText}>Choose from library</Text>
            </TouchableOpacity>
            <View style={styles.photoPickerDivider} />
            <TouchableOpacity style={styles.photoPickerOption} activeOpacity={0.7} onPress={() => setPickerVisible(false)}>
              <Text style={[styles.photoPickerOptionText, styles.photoPickerCancelText]}>Cancel</Text>
            </TouchableOpacity>
          </View>
        ) : null}

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
  photoHint: {
    ...typography.caption,
    color: color.textSecondary,
    marginBottom: space[3],
  },
  photoAddBox: {
    height: 96,
    borderRadius: radius.md,
    borderWidth: 1,
    borderStyle: 'dashed',
    borderColor: color.primary,
    backgroundColor: color.primaryTint,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: space[5],
    gap: space[1],
  },
  photoAddText: {
    ...typography.bodyStrong,
    color: color.primary,
  },
  photoPreviewWrap: {
    height: 160,
    borderRadius: radius.md,
    overflow: 'hidden',
    marginBottom: space[5],
    backgroundColor: color.border,
  },
  photoPreview: {
    width: '100%',
    height: '100%',
  },
  photoRetakeBadge: {
    position: 'absolute',
    right: space[2],
    bottom: space[2],
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: 'rgba(10,27,61,0.75)',
    borderRadius: radius.pill,
    paddingHorizontal: space[3],
    paddingVertical: space[1],
  },
  photoRetakeText: {
    ...typography.caption,
    color: color.textInverse,
  },
  photoPickerCard: {
    backgroundColor: color.surface,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: color.border,
    marginTop: -space[3],
    marginBottom: space[5],
    overflow: 'hidden',
  },
  photoPickerOption: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space[2],
    paddingVertical: space[3],
    paddingHorizontal: space[4],
  },
  photoPickerOptionText: {
    ...typography.body,
    color: color.textPrimary,
  },
  photoPickerCancelText: {
    color: color.textSecondary,
  },
  photoPickerDivider: {
    height: 1,
    backgroundColor: color.border,
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
