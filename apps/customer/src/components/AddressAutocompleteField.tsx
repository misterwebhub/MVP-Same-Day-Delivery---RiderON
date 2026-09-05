import React, { useEffect, useRef, useState } from 'react';
import { ActivityIndicator, StyleSheet, Text, TextInput, TouchableOpacity, View, type KeyboardTypeOptions } from 'react-native';
import { color, radius, space, typography, MIN_TOUCH_TARGET } from '@rideron/design-tokens';
import { apiClient } from '../services/httpClient';

interface PlacePrediction {
  placeId: string;
  description: string;
}

interface AddressAutocompleteFieldProps {
  label?: string;
  value: string;
  onChangeText: (text: string) => void;
  /** Called once a suggestion is picked and its coordinate resolved via Place
   * Details — the only way this field can hand back a lat/lng (there's no
   * GPS fix for a remote delivery address the customer isn't standing at). */
  onCoordinateResolved?: (latitude: number, longitude: number) => void;
  placeholder?: string;
  keyboardType?: KeyboardTypeOptions;
  multiline?: boolean;
  /** Biases autocomplete results toward this point (Zomato/Porter-style
   * "nearby first") — pass the customer's current GPS fix when available. */
  originBias?: { latitude: number; longitude: number };
}

/**
 * Free-text address input that upgrades itself to Google Places Autocomplete
 * (via the backend's /places/* proxy — see PlacesController) and silently
 * degrades to a plain TextInput (no suggestions, no lat/lng) the moment any
 * Places request fails for any reason (backend down, offline, Google quota,
 * no key configured server-side) — per the product requirement that address
 * entry must never hard-block on Google being unavailable.
 *
 * Routed through our own backend rather than calling Google directly because
 * Google's Autocomplete/Details JSON endpoints don't send CORS headers, so a
 * direct browser fetch from Expo web is blocked outright; the proxy also
 * keeps the Google API key server-side instead of in the client bundle.
 *
 * Coordinates are only ever captured by picking a suggestion (Place Details
 * lookup) — plain typed text alone never fabricates a coordinate.
 */
export function AddressAutocompleteField({
  label,
  value,
  onChangeText,
  onCoordinateResolved,
  placeholder,
  keyboardType,
  multiline,
  originBias,
}: AddressAutocompleteFieldProps) {
  const [predictions, setPredictions] = useState<PlacePrediction[]>([]);
  const [searching, setSearching] = useState(false);
  const [resolving, setResolving] = useState(false);
  // Flips permanently once we see any failure (backend/network error,
  // unusable response shape) — after that we behave exactly like a plain
  // TextField for the rest of this field's lifetime rather than retrying a
  // broken integration on every keystroke.
  const disabledRef = useRef(false);
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const requestIdRef = useRef(0);

  useEffect(() => {
    return () => {
      if (debounceRef.current) clearTimeout(debounceRef.current);
    };
  }, []);

  const search = (text: string) => {
    if (debounceRef.current) clearTimeout(debounceRef.current);
    if (disabledRef.current || text.trim().length < 3) {
      setPredictions([]);
      return;
    }
    const thisRequestId = ++requestIdRef.current;
    debounceRef.current = setTimeout(() => {
      setSearching(true);
      apiClient.places
        .autocomplete(text, originBias)
        .then((data) => {
          if (requestIdRef.current !== thisRequestId) return;
          if (Array.isArray(data.predictions)) {
            setPredictions(
              data.predictions.map((p) => ({
                placeId: p.place_id,
                description: p.description,
              })),
            );
          } else {
            // Unexpected shape — treat the integration as unusable from here on.
            disabledRef.current = true;
            setPredictions([]);
          }
        })
        .catch(() => {
          // Backend unreachable, offline, etc. — same graceful fallback.
          disabledRef.current = true;
          setPredictions([]);
        })
        .finally(() => {
          if (requestIdRef.current === thisRequestId) setSearching(false);
        });
    }, 350);
  };

  const onSelectPrediction = (prediction: PlacePrediction) => {
    setPredictions([]);
    onChangeText(prediction.description);
    setResolving(true);
    apiClient.places
      .details(prediction.placeId)
      .then((data) => {
        if (data.location && typeof data.location.lat === 'number' && typeof data.location.lng === 'number') {
          onCoordinateResolved?.(data.location.lat, data.location.lng);
        } else {
          disabledRef.current = true;
        }
        // The autocomplete prediction's description is often just a
        // landmark/POI name (e.g. "Kanpur Central") with no pincode — once
        // Place Details resolves, swap in the full formatted address so the
        // rider actually gets a deliverable address with a pincode.
        if (data.formatted_address) {
          onChangeText(data.formatted_address);
        }
      })
      .catch(() => {
        disabledRef.current = true;
      })
      .finally(() => setResolving(false));
  };

  return (
    <View style={styles.container}>
      {label ? <Text style={styles.label}>{label}</Text> : null}
      <View style={[styles.inputRow, multiline ? styles.inputRowMultiline : null]}>
        <TextInput
          style={[styles.input, multiline ? styles.inputMultiline : null]}
          value={value}
          onChangeText={(text) => {
            onChangeText(text);
            search(text);
          }}
          placeholder={placeholder}
          placeholderTextColor={color.textSecondary}
          keyboardType={keyboardType}
          autoCapitalize="none"
          autoCorrect={false}
          multiline={multiline}
          textAlignVertical={multiline ? 'top' : 'center'}
        />
        {searching || resolving ? <ActivityIndicator size="small" color={color.primary} /> : null}
      </View>
      {predictions.length > 0 ? (
        <View style={styles.dropdown}>
          {predictions.map((prediction) => (
            <TouchableOpacity
              key={prediction.placeId}
              style={styles.predictionRow}
              activeOpacity={0.7}
              onPress={() => onSelectPrediction(prediction)}
            >
              <Text style={styles.predictionText} numberOfLines={2}>
                {prediction.description}
              </Text>
            </TouchableOpacity>
          ))}
        </View>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    width: '100%',
  },
  label: {
    ...typography.caption,
    color: color.textSecondary,
    marginBottom: space[1],
  },
  inputRow: {
    flexDirection: 'row',
    alignItems: 'center',
    minHeight: MIN_TOUCH_TARGET,
    borderWidth: 1,
    borderColor: color.border,
    borderRadius: radius.md,
    backgroundColor: color.surface,
    paddingHorizontal: space[4],
  },
  inputRowMultiline: {
    minHeight: 96,
    alignItems: 'flex-start',
    paddingVertical: space[3],
  },
  input: {
    flex: 1,
    ...typography.body,
    color: color.textPrimary,
    paddingVertical: space[3],
  },
  inputMultiline: {
    minHeight: 72,
  },
  dropdown: {
    marginTop: space[1],
    borderWidth: 1,
    borderColor: color.border,
    borderRadius: radius.md,
    backgroundColor: color.surface,
    overflow: 'hidden',
  },
  predictionRow: {
    paddingVertical: space[3],
    paddingHorizontal: space[4],
    borderBottomWidth: 1,
    borderBottomColor: color.border,
  },
  predictionText: {
    ...typography.body,
    color: color.textPrimary,
  },
});
