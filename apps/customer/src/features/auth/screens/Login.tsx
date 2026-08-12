import React, { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { color, space, typography } from '@rideron/design-tokens';
import { Button } from '../../../components/Button';
import { TextField } from '../../../components/TextField';
import { apiClient } from '../../../services/httpClient';
import { ApiClientError } from '@rideron/api-client';
import type { AuthStackParamList } from '../../../navigation/types';

type Props = NativeStackScreenProps<AuthStackParamList, 'Login'>;

/** Backend validation — RequestOtpRequest.php: /^[6-9]\d{9}$/ (Indian mobile numbers). */
const PHONE_REGEX = /^[6-9]\d{9}$/;

export function Login({ navigation }: Props) {
  const [phone, setPhone] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const handlePhoneChange = (text: string) => {
    setPhone(text.replace(/[^0-9]/g, ''));
    setError(null);
  };

  const onSubmit = async () => {
    if (!PHONE_REGEX.test(phone)) {
      setError('Enter a valid 10-digit mobile number.');
      return;
    }
    setLoading(true);
    setError(null);
    try {
      await apiClient.auth.requestOtp({ phone, purpose: 'login' });
      navigation.navigate('OtpVerify', { phone });
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
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.title}>Enter your mobile number</Text>
        <Text style={styles.subtitle}>We'll send you a one-time code to verify it's you.</Text>
      </View>

      <TextField
        label="Mobile number"
        value={phone}
        onChangeText={handlePhoneChange}
        placeholder="98765 43210"
        keyboardType="number-pad"
        maxLength={10}
        prefix="+91"
        error={error}
        autoFocus
      />

      <View style={styles.footer}>
        <Button title="Send OTP" onPress={onSubmit} loading={loading} disabled={phone.length !== 10} />
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
  footer: {
    marginTop: space[6],
  },
});
