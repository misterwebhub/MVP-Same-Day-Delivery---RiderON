import React, { useEffect, useState } from 'react';
import { ActivityIndicator, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { color, radius, space, typography } from '@rideron/design-tokens';
import { apiClient } from '../../../services/httpClient';
import { Icon } from '../../../components/Icon';
import { storage, type DisplayLanguage } from '../../../services/storage';

const OPTIONS: { key: DisplayLanguage; label: string; native: string }[] = [
  { key: 'en', label: 'English', native: 'English' },
  { key: 'hi', label: 'Hindi', native: 'हिन्दी' },
];

/** Persists a device-local display-language preference (AsyncStorage) and best-effort
 * syncs it to the backend's `preferred_language` profile field for record-keeping.
 * There is no in-app translation catalog yet, so this intentionally does not claim to
 * retranslate the UI — the note below says so explicitly rather than faking it. */
export function Language() {
  const [selected, setSelected] = useState<DisplayLanguage | null>(null);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    storage.getDisplayLanguage().then(setSelected);
  }, []);

  const onSelect = async (lang: DisplayLanguage) => {
    if (lang === selected || saving) return;
    setSaving(true);
    setSelected(lang);
    await storage.setDisplayLanguage(lang);
    try {
      // Best-effort: record the preference on the backend profile too. This is a
      // read-model field only (UpdateProfilePayload doesn't accept it), so if the
      // server rejects it the local preference above still stands.
      await apiClient.http.request('/profile', { method: 'PATCH', body: { preferred_language: lang } });
    } catch {
      // Non-fatal — local preference already saved above.
    } finally {
      setSaving(false);
    }
  };

  return (
    <View style={styles.container}>
      <Text style={styles.note}>
        Choose your preferred language. Full in-app translation is coming soon — for now this saves your preference for
        future updates.
      </Text>
      {selected === null ? (
        <ActivityIndicator color={color.primary} style={styles.loader} />
      ) : (
        OPTIONS.map((option) => (
          <TouchableOpacity
            key={option.key}
            style={[styles.row, selected === option.key && styles.rowSelected]}
            onPress={() => onSelect(option.key)}
            activeOpacity={0.8}
          >
            <View>
              <Text style={styles.rowLabel}>{option.label}</Text>
              <Text style={styles.rowNative}>{option.native}</Text>
            </View>
            {selected === option.key ? <Icon name="checkmark-circle" size={22} color={color.primary} /> : null}
          </TouchableOpacity>
        ))
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: color.background,
    padding: space[5],
  },
  note: {
    ...typography.caption,
    color: color.textSecondary,
    marginBottom: space[5],
  },
  loader: {
    marginTop: space[6],
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: color.surface,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: color.border,
    padding: space[4],
    marginBottom: space[3],
  },
  rowSelected: {
    borderColor: color.primary,
    backgroundColor: color.primaryTint,
  },
  rowLabel: {
    ...typography.bodyStrong,
    color: color.textPrimary,
  },
  rowNative: {
    ...typography.caption,
    color: color.textSecondary,
    marginTop: 2,
  },
});
