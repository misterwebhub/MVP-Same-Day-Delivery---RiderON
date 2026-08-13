import React, { useEffect, useState } from 'react';
import { Linking, StyleSheet, Text, View } from 'react-native';
import { color, radius, space, typography } from '@rideron/design-tokens';
import type { OrderOtpField } from '@rideron/types';
import { apiClient } from '../../../services/httpClient';
import { ApiClientError } from '@rideron/api-client';
import { maskPhone } from '../../../utils/phone';

/** backend/config/otp.php: resend_cooldown_seconds=60. */
const RESEND_COOLDOWN_SECONDS = 60;

interface OtpResendCardProps {
  title: string;
  /** Who this code is for, shown as a hint under the code. */
  hint: string;
  phone: string;
  orderId: number;
  purpose: 'pickup' | 'delivery';
  /** From Order.pickup_otp / Order.delivery_otp — see app/Http/Resources/OrderResource.php. */
  otp: OrderOtpField | null | undefined;
  /** Called with the freshly-resent OTP field so the parent can update its Order state. */
  onResent?: (field: OrderOtpField) => void;
  /** When set, shows a "Share via WhatsApp" action for handing the code to the receiver. */
  whatsappShareLabel?: string;
}

/**
 * Pickup/Delivery OTP card — reused on Confirmation (right after booking) and
 * OrderDetails (while tracking). The backend now surfaces the live plaintext
 * code directly (app/Http/Resources/OrderResource.php's pickup_otp/delivery_otp,
 * backed by a short-lived cache in App\Services\Otp\OtpService — TTL matches
 * the OTP's own expiry, cleared the instant it's verified), so the code is
 * shown in-app instead of only relying on SMS delivery. `otp.code` can still
 * be null (already verified/expired, or the display cache missed) — in that
 * case this falls back to "sent via SMS" + a real resend action, same as before.
 */
export function OtpResendCard({ title, hint, phone, orderId, purpose, otp, onResent, whatsappShareLabel }: OtpResendCardProps) {
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
      const result = await apiClient.orders.resendOtp(orderId, purpose);
      setCooldown(RESEND_COOLDOWN_SECONDS);
      setMessage(result.code ? 'New code ready below.' : 'Code resent via SMS.');
      onResent?.({ status: 'pending', code: result.code, expires_at: result.expires_at, resend_count: result.resend_count });
    } catch (e) {
      setMessage(e instanceof ApiClientError ? e.message : 'Could not resend the code.');
    } finally {
      setResending(false);
    }
  };

  const verified = otp?.status === 'verified';
  const expired = otp?.status === 'expired';

  const onShareWhatsapp = async () => {
    if (!otp?.code) return;
    const text = encodeURIComponent(
      `Your RiderON ${purpose} code is ${otp.code}. Share it only with the verified rider in person.`,
    );
    const waUrl = `whatsapp://send?text=${text}${phone ? `&phone=91${phone.replace(/\D/g, '')}` : ''}`;
    try {
      const canOpen = await Linking.canOpenURL(waUrl);
      if (canOpen) {
        await Linking.openURL(waUrl);
      } else {
        setMessage('WhatsApp is not installed on this device.');
      }
    } catch {
      setMessage('Could not open WhatsApp.');
    }
  };

  return (
    <View style={styles.card}>
      <Text style={styles.title}>{title}</Text>

      {verified ? (
        <Text style={styles.body}>Verified.</Text>
      ) : otp?.code ? (
        <>
          <Text style={styles.code}>{otp.code}</Text>
          <Text style={styles.hint}>{hint}</Text>
        </>
      ) : (
        <Text style={styles.body}>
          {expired ? 'This code expired.' : phone ? `Sent via SMS to +91 ${maskPhone(phone)}.` : 'Sent via SMS.'} Share this code only
          with the verified rider.
        </Text>
      )}

      <Text style={[styles.resendLink, (cooldown > 0 || resending) && styles.resendLinkDisabled]} onPress={cooldown > 0 || resending ? undefined : onResend}>
        {cooldown > 0 ? `Resend in ${cooldown}s` : resending ? 'Resending...' : verified ? 'Resend code' : 'Get a new code'}
      </Text>
      {whatsappShareLabel && otp?.code ? (
        <Text style={styles.whatsappLink} onPress={onShareWhatsapp}>
          {whatsappShareLabel}
        </Text>
      ) : null}
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
  code: {
    ...typography.display,
    color: color.primary,
    letterSpacing: 6,
    marginBottom: space[1],
  },
  hint: {
    ...typography.caption,
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
  whatsappLink: {
    ...typography.bodyStrong,
    color: color.success,
    marginTop: space[2],
  },
  message: {
    ...typography.caption,
    color: color.textSecondary,
    marginTop: space[2],
  },
});
