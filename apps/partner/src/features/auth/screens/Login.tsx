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

/** Backend validation — PartnerLoginRequest.php: /^[6-9]\d{9}$/ (Indian mobile numbers). */
const PHONE_REGEX = /^[6-9]\d{9}$/;

/**
 * Partner accounts are provisioned by ops (see backend
 * DeliveryPartnerSeeder for the dev/testing account), not self-registered,
 * so this is a plain phone+password login against POST /auth/partner/login
 * — no OTP step, unlike the customer app.
 */
export function Login() {
  const { signIn } = useAuth();
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const bottomPadding = useSafeBottomPadding(space[6]);

  const handlePhoneChange = (text: string) => {
    setPhone(text.replace(/[^0-9]/g, ''));
    setError(null);
  };

  const onSubmit = async () => {
    if (!PHONE_REGEX.test(phone)) {
      setError('Enter a valid 10-digit mobile number.');
      return;
    }
    if (password.length === 0) {
      setError('Enter your password.');
      return;
    }
    setLoading(true);
    setError(null);
    try {
      await apiClient.auth.partnerLogin({ phone, password });
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
        <Text style={styles.title}>RiderON Partner</Text>
        <Text style={styles.subtitle}>Sign in with your registered mobile number and password.</Text>
      </View>

      <TextField
        label="Mobile number"
        value={phone}
        onChangeText={handlePhoneChange}
        placeholder="98765 43210"
        keyboardType="number-pad"
        maxLength={10}
        prefix="+91"
        autoFocus
      />

      <View style={styles.spacer} />

      <TextField
        label="Password"
        value={password}
        onChangeText={(text) => {
          setPassword(text);
          setError(null);
        }}
        placeholder="Password"
        secureTextEntry
        error={error}
      />

      <View style={[styles.footer, { paddingBottom: bottomPadding }]}>
        <Button
          title="Sign In"
          onPress={onSubmit}
          loading={loading}
          disabled={phone.length !== 10 || password.length === 0}
        />
      </View>
    </KeyboardSafeScreen>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: color.background,
    padding: space[6],
    justifyContent: 'center',
  },
  header: {
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
  spacer: {
    height: space[4],
  },
  footer: {
    marginTop: space[6],
  },
});
