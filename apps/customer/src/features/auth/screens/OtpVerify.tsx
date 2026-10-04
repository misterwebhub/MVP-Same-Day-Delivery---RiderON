import React, { useEffect, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { color, space, typography } from '@rideron/design-tokens';
import { Button } from '../../../components/Button';
import { TextField } from '../../../components/TextField';
import { KeyboardSafeScreen } from '../../../components/KeyboardSafeScreen';
import { useSafeBottomPadding } from '../../../hooks/useSafeBottomPadding';
import { apiClient } from '../../../services/httpClient';
import { useAuth } from '../../../hooks/useAuth';
import { ApiClientError } from '@rideron/api-client';
import type { AuthStackParamList } from '../../../navigation/types';

type Props = NativeStackScreenProps<AuthStackParamList, 'OtpVerify'>;

/** backend/config/otp.php: length=4, resend_cooldown_seconds=60. */
const OTP_LENGTH = 4;
const RESEND_COOLDOWN_SECONDS = 60;

export function OtpVerify({ route, navigation }: Props) {
  const { phone } = route.params;
  const { signIn } = useAuth();
  const [otp, setOtp] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [resending, setResending] = useState(false);
  const [cooldown, setCooldown] = useState(RESEND_COOLDOWN_SECONDS);
  const bottomPadding = useSafeBottomPadding(space[6]);

  useEffect(() => {
    if (cooldown <= 0) return;
    const timer = setTimeout(() => setCooldown((s) => s - 1), 1000);
    return () => clearTimeout(timer);
  }, [cooldown]);

  const handleOtpChange = (text: string) => {
    setOtp(text.replace(/[^0-9]/g, ''));
    setError(null);
  };

  const onSubmit = async () => {
    if (otp.length !== OTP_LENGTH) {
      setError(`Enter the ${OTP_LENGTH}-digit code sent to +91 ${phone}.`);
      return;
    }
    setLoading(true);
    setError(null);
    try {
      const result = await apiClient.auth.verifyOtp({ phone, otp });
      if (result.is_new_user) {
        // New user — tokens are already persisted, but useAuth().status only
        // flips to 'authenticated' once ProfileSetup completes (see useAuth.tsx).
        navigation.navigate('ProfileSetup');
      } else {
        signIn();
      }
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

  const onResend = async () => {
    setResending(true);
    setError(null);
    try {
      await apiClient.auth.requestOtp({ phone, purpose: 'login' });
      setCooldown(RESEND_COOLDOWN_SECONDS);
    } catch (e) {
      if (e instanceof ApiClientError) {
        setError(e.message);
      }
    } finally {
      setResending(false);
    }
  };

  return (
    <KeyboardSafeScreen style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.title}>Verify your number</Text>
        <Text style={styles.subtitle}>Enter the {OTP_LENGTH}-digit code sent to +91 {phone}.</Text>
      </View>

      <TextField
        label="OTP"
        value={otp}
        onChangeText={handleOtpChange}
        placeholder="0000"
        keyboardType="number-pad"
        maxLength={OTP_LENGTH}
        error={error}
        autoFocus
      />

      <Text
        style={[styles.resendText, cooldown > 0 && styles.resendTextDisabled]}
        onPress={cooldown > 0 || resending ? undefined : onResend}
      >
        {cooldown > 0 ? `Resend code in ${cooldown}s` : resending ? 'Sending...' : 'Resend code'}
      </Text>

      <View style={[styles.footer, { paddingBottom: bottomPadding }]}>
        <Button title="Verify" onPress={onSubmit} loading={loading} disabled={otp.length !== OTP_LENGTH} />
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
  resendText: {
    ...typography.bodyStrong,
    color: color.primary,
    marginTop: space[4],
  },
  resendTextDisabled: {
    color: color.textSecondary,
  },
  footer: {
    marginTop: space[6],
  },
});
