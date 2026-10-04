import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { color, radius, space, typography } from '@rideron/design-tokens';
import type { SavedContact } from '@rideron/types';
import { ApiClientError } from '@rideron/api-client';
import { apiClient } from '../../../services/httpClient';
import { Button } from '../../../components/Button';
import { Chip } from '../../../components/Chip';
import { Icon } from '../../../components/Icon';
import { TextField } from '../../../components/TextField';
import { KeyboardSafeScreen } from '../../../components/KeyboardSafeScreen';
import { confirmAction } from '../../../utils/confirm';

/** Real saved sender/receiver address book — GET/POST/DELETE /profile/saved-contacts.
 * Replaces the ScreenStub placeholder. `type` is constrained to 'sender'/'receiver'
 * to match the backend's StoreSavedContactRequest enum. */
export function SavedContacts() {
  const [contacts, setContacts] = useState<SavedContact[] | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [showForm, setShowForm] = useState(false);
  const [type, setType] = useState<'sender' | 'receiver'>('sender');
  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [landmark, setLandmark] = useState('');
  const [formError, setFormError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  const load = useCallback(() => {
    setError(null);
    return apiClient.profile
      .listSavedContacts()
      .then(setContacts)
      .catch((e) => setError(e instanceof ApiClientError ? e.message : 'Could not load saved addresses.'));
  }, []);

  useEffect(() => {
    setLoading(true);
    load().finally(() => setLoading(false));
  }, [load]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await load();
    setRefreshing(false);
  }, [load]);

  const resetForm = () => {
    setType('sender');
    setName('');
    setPhone('');
    setLandmark('');
    setFormError(null);
  };

  const onAdd = async () => {
    if (name.trim().length === 0) {
      setFormError('Enter a name.');
      return;
    }
    if (phone.trim().length < 10) {
      setFormError('Enter a valid phone number.');
      return;
    }
    setSaving(true);
    setFormError(null);
    try {
      const created = await apiClient.profile.createSavedContact({
        type,
        name: name.trim(),
        phone: phone.trim(),
        landmark: landmark.trim() || null,
      });
      setContacts((prev) => [created, ...(prev ?? [])]);
      resetForm();
      setShowForm(false);
    } catch (e) {
      setFormError(e instanceof ApiClientError ? e.message : 'Could not save this address.');
    } finally {
      setSaving(false);
    }
  };

  const onDelete = (contact: SavedContact) => {
    confirmAction(
      'Remove address',
      `Remove "${contact.name}" from your saved addresses?`,
      'Remove',
      async () => {
        const prev = contacts;
        setContacts((cur) => (cur ?? []).filter((c) => c.id !== contact.id));
        try {
          await apiClient.profile.deleteSavedContact(contact.id);
        } catch {
          setContacts(prev ?? null);
          confirmAction('Could not remove', 'Please try again.', 'OK', () => {});
        }
      },
      true,
    );
  };

  return (
    <KeyboardSafeScreen style={styles.container}>
      <FlatList
        data={contacts ?? []}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={styles.listContent}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={color.primary} />}
        ListHeaderComponent={
          <View style={styles.header}>
            {!showForm ? (
              <Button title="+ Add address" variant="secondary" onPress={() => setShowForm(true)} />
            ) : (
              <View style={styles.form}>
                <View style={styles.typeRow}>
                  <Chip label="Sender" selected={type === 'sender'} onPress={() => setType('sender')} />
                  <Chip label="Receiver" selected={type === 'receiver'} onPress={() => setType('receiver')} />
                </View>
                <View style={styles.fieldGap} />
                <TextField label="Name" value={name} onChangeText={setName} placeholder="Full name" />
                <View style={styles.fieldGap} />
                <TextField label="Phone" value={phone} onChangeText={(t) => setPhone(t.replace(/[^0-9]/g, ''))} placeholder="10-digit mobile" keyboardType="number-pad" maxLength={10} prefix="+91" />
                <View style={styles.fieldGap} />
                <TextField label="Landmark (optional)" value={landmark} onChangeText={setLandmark} placeholder="Near..." />
                {formError ? <Text style={styles.error}>{formError}</Text> : null}
                <View style={styles.formButtonRow}>
                  <Button
                    title="Cancel"
                    variant="secondary"
                    style={styles.formButton}
                    onPress={() => {
                      resetForm();
                      setShowForm(false);
                    }}
                  />
                  <Button title="Save" style={styles.formButton} onPress={onAdd} loading={saving} />
                </View>
              </View>
            )}
          </View>
        }
        ListEmptyComponent={
          !loading ? (
            <View style={styles.empty}>
              <Text style={styles.emptyText}>{error ?? 'No saved addresses yet — add a sender or receiver you book often.'}</Text>
            </View>
          ) : loading ? (
            <ActivityIndicator color={color.primary} style={styles.loader} />
          ) : null
        }
        renderItem={({ item }) => (
          <View style={styles.row}>
            <View style={styles.rowIcon}>
              <Icon name={item.type === 'sender' ? 'arrow-up-circle-outline' : 'arrow-down-circle-outline'} size={20} color={color.primary} />
            </View>
            <View style={styles.rowText}>
              <Text style={styles.rowName}>{item.name}</Text>
              <Text style={styles.rowMeta}>
                +91 {item.phone} · {item.type === 'sender' ? 'Sender' : 'Receiver'}
              </Text>
              {item.landmark ? <Text style={styles.rowMeta}>{item.landmark}</Text> : null}
            </View>
            <TouchableOpacity accessibilityRole="button" onPress={() => onDelete(item)} hitSlop={8}>
              <Icon name="trash-outline" size={20} color={color.error} />
            </TouchableOpacity>
          </View>
        )}
      />
    </KeyboardSafeScreen>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: color.background,
  },
  listContent: {
    padding: space[5],
    paddingBottom: space[8],
    flexGrow: 1,
  },
  header: {
    marginBottom: space[4],
  },
  form: {
    backgroundColor: color.surface,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: color.border,
    padding: space[4],
  },
  typeRow: {
    flexDirection: 'row',
    gap: space[2],
  },
  fieldGap: {
    height: space[3],
  },
  formButtonRow: {
    flexDirection: 'row',
    gap: space[3],
    marginTop: space[4],
  },
  formButton: {
    flex: 1,
  },
  loader: {
    marginTop: space[6],
  },
  empty: {
    alignItems: 'center',
    paddingTop: space[6],
  },
  emptyText: {
    ...typography.body,
    color: color.textSecondary,
    textAlign: 'center',
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: color.surface,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: color.border,
    padding: space[4],
    marginBottom: space[3],
  },
  rowIcon: {
    marginRight: space[3],
  },
  rowText: {
    flex: 1,
  },
  rowName: {
    ...typography.bodyStrong,
    color: color.textPrimary,
  },
  rowMeta: {
    ...typography.caption,
    color: color.textSecondary,
    marginTop: 2,
  },
  error: {
    ...typography.caption,
    color: color.error,
    marginTop: space[2],
  },
});
