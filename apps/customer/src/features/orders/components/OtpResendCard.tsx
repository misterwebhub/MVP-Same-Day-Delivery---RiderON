import React, { useEffect, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { color, radius, space, typography } from '@rideron/design-tokens';
import { apiClient } from '../../../services/httpClient';
import { ApiClientError } from '@rideron/api-client';
import { maskPhone } from '../../../utils/phone';

/** backend/config/otp.php: resend_cooldown_seconds=60. */
const RESEND_COOLDOWN_SECONDS = 60;

interface OtpResendCardProps {
  title: string;
  phone: string;
  orderId: number;
  purpose: 'pickup' | 'delivery';
}

/**
 * Pickup/Delivery OTP status card — reused on Confirmation (right after
 * booking) and OrderDetails (while tracking). The OTP digits themselves are
 * never returned by the API (OtpVerification.otp_hash is $hidden server-side,
 * see backend/app/Models/OtpVerification.php) — only SMS delivery + a real
 * resend action, so this never fabricates a code client-side.
 */
export function OtpResendCard({ title, phone, orderId, purpose }: OtpResendCardProps) {
  const [cooldown, setCooldown] = useState(0);
  const [resending, setResending] = useState(false);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    if (cooldown <= 0) return;
    const t = setTimeout(() => setCooldown((s) => s - 1), 1000);
    return () => clearTimeout(t);
  }, [cooldown]);

  const onResend = async () => {
    setResending(true);
    setMessage(null);
    try {
      await apiClient.orders.resendOtp(orderId, purpose);
      setCooldown(RESEND_COOLDOWN_SECONDS);
      setMessage('Code resent.');
    } catch (e) {
      setMessage(e instanceof ApiClientError ? e.message : 'Could not resend the code.');
    } finally {
      setResending(false);
    }
  };

  return (
    <View style={styles.card}>
      <Text style={styles.title}>{title}</Text>
      <Text style={styles.body}>
        {phone ? `Sent via SMS to +91 ${maskPhone(phone)}.` : 'Sent via SMS.'} Share this code only with the verified rider.
      </Text>
      <Text style={[styles.resendLink, (cooldown > 0 || resending) && styles.resendLinkDisabled]} onPress={cooldown > 0 || resending ? undefined : onResend}>
        {cooldown > 0 ? `Resend in ${cooldown}s` : resending ? 'Resending...' : 'Resend code'}
      </Text>
      {message ? <Text style={styles.message}>{message}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    width: '100%',
    backgroundColor: color.surface,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: color.border,
    padding: space[4],
    marginBottom: space[4],
  },
  title: {
    ...typography.h2,
    color: color.textPrimary,
    marginBottom: space[2],
  },
  body: {
    ...typography.body,
    color: color.textSecondary,
    marginBottom: space[3],
  },
  resendLink: {
    ...typography.bodyStrong,
    color: color.primary,
  },
  resendLinkDisabled: {
    color: color.textSecondary,
  },
  message: {
    ...typography.caption,
    color: color.textSecondary,
    marginTop: space[2],
  },
});
