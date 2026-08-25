import React, { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { color, space, typography } from '@rideron/design-tokens';
import { Button } from '../../../components/Button';
import { TextField } from '../../../components/TextField';
import { KeyboardSafeScreen } from '../../../components/KeyboardSafeScreen';
import { useSafeBottomPadding } from '../../../hooks/useSafeBottomPadding';
import { apiClient } from '../../../services/httpClient';
import { useAuth } from '../../../hooks/useAuth';
import { ApiClientError } from '@rideron/api-client';

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/** Only reached for new users (is_new_user:true from VerifyOtp) — see OtpVerify.tsx. */
export function ProfileSetup() {
  const { signIn } = useAuth();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const bottomPadding = useSafeBottomPadding(space[6]);

  const onSubmit = async () => {
    if (name.trim().length === 0) {
      setError('Please enter your name.');
      return;
    }
    if (email.trim().length > 0 && !EMAIL_REGEX.test(email.trim())) {
      setError('Enter a valid email address, or leave it blank.');
      return;
    }
    setLoading(true);
    setError(null);
    try {
      await apiClient.auth.completeProfile({
        name: name.trim(),
        email: email.trim().length > 0 ? email.trim() : null,
      });
      signIn();
    } catch (e) {
      if (e instanceof ApiClientError) {
        setError(e.isNetworkError ? 'No connection — check your network and try again.' : e.message);
      } else {
        setError('Something went wrong. Please try again.');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <KeyboardSafeScreen style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.title}>Tell us about you</Text>
        <Text style={styles.subtitle}>Just your name to get started — email is optional.</Text>
      </View>

      <View style={styles.field}>
        <TextField label="Full name" value={name} onChangeText={setName} placeholder="Your name" autoFocus />
      </View>
      <View style={styles.field}>
        <TextField
          label="Email (optional)"
          value={email}
          onChangeText={setEmail}
          placeholder="you@example.com"
          keyboardType="email-address"
        />
      </View>

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <View style={[styles.footer, { paddingBottom: bottomPadding }]}>
        <Button title="Continue" onPress={onSubmit} loading={loading} disabled={name.trim().length === 0} />
      </View>
    </KeyboardSafeScreen>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: color.background,
    padding: space[6],
  },
  header: {
    marginTop: space[8],
    marginBottom: space[6],
  },
  title: {
    ...typography.h1,
    color: color.textPrimary,
    marginBottom: space[2],
  },
  subtitle: {
    ...typography.body,
    color: color.textSecondary,
  },
  field: {
    marginBottom: space[4],
  },
  error: {
    ...typography.caption,
    color: color.error,
  },
  footer: {
    marginTop: space[6],
  },
});
