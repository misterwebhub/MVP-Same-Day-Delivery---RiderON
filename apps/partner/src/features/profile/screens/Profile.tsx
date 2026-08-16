import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Alert, StyleSheet, Text, View } from 'react-native';
import { color, radius, space, typography } from '@rideron/design-tokens';
import type { Profile as ProfileType } from '@rideron/types';
import { ApiClientError } from '@rideron/api-client';
import { Button } from '../../../components/Button';
import { Icon } from '../../../components/Icon';
import { apiClient } from '../../../services/httpClient';
import { useAuth } from '../../../hooks/useAuth';

/** First letter of each of up to the first two words — e.g. "Ravi Kumar" -> "RK",
 * falling back to a generic rider glyph while the name is still loading. */
function initials(name: string | null | undefined): string {
  if (!name) return '';
  return name
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join('');
}

/** Basic partner info (GET /profile, same generic endpoint as the customer app) + sign out. */
export function Profile() {
  const { signOut } = useAuth();
  const [profile, setProfile] = useState<ProfileType | null>(null);
  const [loading, setLoading] = useState(true);
  const [signingOut, setSigningOut] = useState(false);

  useEffect(() => {
    let cancelled = false;
    apiClient.profile
      .get()
      .then((result) => {
        if (!cancelled) setProfile(result);
      })
      .catch(() => {})
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, []);

  const onSignOut = () => {
    Alert.alert('Sign out?', 'You will need to sign in again to receive assignments.', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Sign out',
        style: 'destructive',
        onPress: async () => {
          setSigningOut(true);
          try {
            await signOut();
          } catch (e) {
            Alert.alert('Could not sign out', e instanceof ApiClientError ? e.message : 'Please try again.');
          } finally {
            setSigningOut(false);
          }
        },
      },
    ]);
  };

  if (loading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator color={color.primary} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Profile</Text>

      <View style={styles.card}>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>{initials(profile?.name) || 'RP'}</Text>
        </View>
        <View style={styles.cardText}>
          <Text style={styles.name}>{profile?.name ?? 'Delivery Partner'}</Text>
          <View style={styles.detailRow}>
            <Icon name="call-outline" size={14} color={color.textSecondary} />
            <Text style={styles.detail}>+91 {profile?.phone}</Text>
          </View>
          {profile?.email ? (
            <View style={styles.detailRow}>
              <Icon name="mail-outline" size={14} color={color.textSecondary} />
              <Text style={styles.detail}>{profile.email}</Text>
            </View>
          ) : null}
        </View>
      </View>

      <View style={styles.footer}>
        <Button title="Sign out" variant="secondary" onPress={onSignOut} loading={signingOut} />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: color.background,
    padding: space[6],
  },
  centered: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: color.background,
  },
  title: {
    ...typography.h1,
    color: color.textPrimary,
    marginBottom: space[4],
  },
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: color.surface,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: color.border,
    padding: space[5],
    shadowColor: color.shadow,
    shadowOpacity: 0.06,
    shadowRadius: 10,
    shadowOffset: { width: 0, height: 3 },
    elevation: 2,
  },
  avatar: {
    width: 56,
    height: 56,
    borderRadius: radius.pill,
    backgroundColor: color.secondary,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: space[4],
  },
  avatarText: {
    ...typography.h2,
    color: color.textInverse,
  },
  cardText: {
    flex: 1,
  },
  name: {
    ...typography.h2,
    color: color.textPrimary,
  },
  detailRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space[1],
    marginTop: space[1],
  },
  detail: {
    ...typography.body,
    color: color.textSecondary,
  },
  footer: {
    marginTop: space[6],
  },
});
