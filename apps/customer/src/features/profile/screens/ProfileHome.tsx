import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { color, radius, space, typography } from '@rideron/design-tokens';
import type { Profile } from '@rideron/types';
import { ApiClientError } from '@rideron/api-client';
import { apiClient } from '../../../services/httpClient';
import { Button } from '../../../components/Button';
import { Icon, IconName } from '../../../components/Icon';
import { TextField } from '../../../components/TextField';
import { useAuth } from '../../../hooks/useAuth';
import type { ProfileStackParamList } from '../../../navigation/types';
import { confirmAction } from '../../../utils/confirm';

type Props = NativeStackScreenProps<ProfileStackParamList, 'ProfileHome'>;

const MENU_ITEMS: { icon: IconName; label: string; route: keyof ProfileStackParamList }[] = [
  { icon: 'people-outline', label: 'Saved Addresses', route: 'SavedContacts' },
  { icon: 'notifications-outline', label: 'Notifications', route: 'Notifications' },
  { icon: 'language-outline', label: 'Language', route: 'Language' },
  { icon: 'document-text-outline', label: 'Terms & Conditions', route: 'Legal' },
];

/** Real profile screen — GET/PATCH /profile, saved-contacts/notifications/language/legal
 * menu, and logout via useAuth().signOut(). Replaces the ScreenStub placeholder. */
export function ProfileHome({ navigation }: Props) {
  const { signOut } = useAuth();
  const [profile, setProfile] = useState<Profile | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [editing, setEditing] = useState(false);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [saving, setSaving] = useState(false);
  const [saveError, setSaveError] = useState<string | null>(null);

  const load = useCallback(() => {
    setLoading(true);
    setError(null);
    apiClient.profile
      .get()
      .then((result) => {
        setProfile(result);
        setName(result.name ?? '');
        setEmail(result.email ?? '');
      })
      .catch((e) => setError(e instanceof ApiClientError ? e.message : 'Could not load your profile.'))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const onSave = async () => {
    setSaving(true);
    setSaveError(null);
    try {
      const updated = await apiClient.profile.update({
        name: name.trim() || undefined,
        email: email.trim() ? email.trim() : null,
      });
      setProfile(updated);
      setEditing(false);
    } catch (e) {
      setSaveError(e instanceof ApiClientError ? e.message : 'Could not save your changes.');
    } finally {
      setSaving(false);
    }
  };

  const onLogout = () => {
    confirmAction('Log out', 'Are you sure you want to log out?', 'Log out', () => signOut(), true);
  };

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      {loading ? <ActivityIndicator color={color.primary} style={styles.loader} /> : null}

      {!loading && error && !profile ? (
        <View style={styles.errorCard}>
          <Text style={styles.error}>{error}</Text>
          <Button title="Retry" onPress={load} variant="secondary" style={styles.retryButton} />
        </View>
      ) : null}

      {!loading && profile ? (
        <View style={styles.card}>
          <View style={styles.avatarRow}>
            <View style={styles.avatar}>
              <Text style={styles.avatarInitial}>{(profile.name?.trim()?.[0] ?? profile.phone[0] ?? '?').toUpperCase()}</Text>
            </View>
            <View style={styles.avatarText}>
              <Text style={styles.name}>{profile.name?.trim() || 'Add your name'}</Text>
              <Text style={styles.phone}>+91 {profile.phone}</Text>
            </View>
            {!editing ? (
              <TouchableOpacity accessibilityRole="button" onPress={() => setEditing(true)} hitSlop={8}>
                <Icon name="create-outline" size={20} color={color.primary} />
              </TouchableOpacity>
            ) : null}
          </View>

          {editing ? (
            <View style={styles.editForm}>
              <TextField label="Full name" value={name} onChangeText={setName} placeholder="Your name" />
              <View style={styles.fieldGap} />
              <TextField label="Email (optional)" value={email} onChangeText={setEmail} placeholder="you@example.com" keyboardType="email-address" />
              {saveError ? <Text style={styles.error}>{saveError}</Text> : null}
              <View style={styles.editButtonRow}>
                <Button
                  title="Cancel"
                  variant="secondary"
                  style={styles.editButton}
                  onPress={() => {
                    setEditing(false);
                    setSaveError(null);
                    setName(profile.name ?? '');
                    setEmail(profile.email ?? '');
                  }}
                />
                <Button title="Save" style={styles.editButton} onPress={onSave} loading={saving} />
              </View>
            </View>
          ) : (
            <Text style={styles.email}>{profile.email?.trim() || 'No email on file'}</Text>
          )}
        </View>
      ) : null}

      {!loading && profile ? (
        <View style={styles.menuCard}>
          {MENU_ITEMS.map((item, i) => (
            <TouchableOpacity
              key={item.route}
              style={[styles.menuRow, i === MENU_ITEMS.length - 1 && styles.menuRowLast]}
              onPress={() => navigation.navigate(item.route as never)}
              activeOpacity={0.75}
            >
              <Icon name={item.icon} size={20} color={color.textSecondary} />
              <Text style={styles.menuLabel}>{item.label}</Text>
              <Icon name="chevron-forward" size={18} color={color.textSecondary} />
            </TouchableOpacity>
          ))}
        </View>
      ) : null}

      {!loading && profile ? <Button title="Log out" variant="secondary" onPress={onLogout} style={styles.logoutButton} /> : null}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: color.background,
  },
  content: {
    padding: space[5],
    paddingBottom: space[8],
  },
  loader: {
    marginTop: space[8],
  },
  errorCard: {
    alignItems: 'center',
    paddingVertical: space[8],
  },
  retryButton: {
    marginTop: space[4],
  },
  card: {
    backgroundColor: color.surface,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: color.border,
    padding: space[5],
    marginBottom: space[4],
  },
  avatarRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  avatar: {
    width: 52,
    height: 52,
    borderRadius: radius.pill,
    backgroundColor: color.primaryTint,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: space[3],
  },
  avatarInitial: {
    ...typography.h2,
    color: color.primary,
  },
  avatarText: {
    flex: 1,
  },
  name: {
    ...typography.h2,
    color: color.textPrimary,
  },
  phone: {
    ...typography.caption,
    color: color.textSecondary,
    marginTop: 2,
  },
  email: {
    ...typography.body,
    color: color.textSecondary,
    marginTop: space[3],
  },
  editForm: {
    marginTop: space[4],
  },
  fieldGap: {
    height: space[3],
  },
  editButtonRow: {
    flexDirection: 'row',
    gap: space[3],
    marginTop: space[4],
  },
  editButton: {
    flex: 1,
  },
  menuCard: {
    backgroundColor: color.surface,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: color.border,
    marginBottom: space[6],
    overflow: 'hidden',
  },
  menuRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space[3],
    paddingVertical: space[4],
    paddingHorizontal: space[5],
    borderBottomWidth: 1,
    borderBottomColor: color.border,
  },
  menuRowLast: {
    borderBottomWidth: 0,
  },
  menuLabel: {
    ...typography.body,
    color: color.textPrimary,
    flex: 1,
  },
  logoutButton: {
    marginTop: space[1],
  },
  error: {
    ...typography.caption,
    color: color.error,
    textAlign: 'center',
  },
});
