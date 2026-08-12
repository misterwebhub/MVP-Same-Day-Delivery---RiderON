import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Alert, StyleSheet, Text, View } from 'react-native';
import { color, radius, space, typography } from '@rideron/design-tokens';
import type { Profile as ProfileType } from '@rideron/types';
import { ApiClientError } from '@rideron/api-client';
import { Button } from '../../../components/Button';
import { apiClient } from '../../../services/httpClient';
import { useAuth } from '../../../hooks/useAuth';

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
        <Text style={styles.name}>{profile?.name ?? 'Delivery Partner'}</Text>
        <Text style={styles.detail}>+91 {profile?.phone}</Text>
        {profile?.email ? <Text style={styles.detail}>{profile.email}</Text> : null}
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
    backgroundColor: color.surface,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: color.border,
    padding: space[5],
  },
  name: {
    ...typography.h2,
    color: color.textPrimary,
  },
  detail: {
    ...typography.body,
    color: color.textSecondary,
    marginTop: space[1],
  },
  footer: {
    marginTop: space[6],
  },
});
