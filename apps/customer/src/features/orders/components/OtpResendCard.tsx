import React from 'react';
import { Linking, StyleSheet, Text, View } from 'react-native';
import { color, radius, space, typography } from '@rideron/design-tokens';
import type { OrderOtpField } from '@rideron/types';
import { Icon } from '../../../components/Icon';
import { maskPhone } from '../../../utils/phone';

interface OtpResendCardProps {
  title: string;
  /** Who this code is for, shown as a hint under the code. */
  hint: string;
  phone: string;
  purpose: 'pickup' | 'delivery';
  /** From Order.pickup_otp / Order.delivery_otp — see app/Http/Resources/OrderResource.php. */
  otp: OrderOtpField | null | undefined;
  /** When set, shows a "Share via WhatsApp" action for handing the code to the receiver. */
  whatsappShareLabel?: string;
}

/**
 * Pickup/Delivery OTP card — reused on Confirmation (right after booking) and
 * OrderDetails (while tracking). Display-only: only the assigned rider can
 * regenerate a code (Partner app's "Resend/regenerate OTP" button, backed by
 * POST partner/assignments/{order}/otp/{purpose}/regenerate) — the customer
 * app deliberately has no equivalent action anymore, so a customer can never
 * mint a fresh code themselves. If the code isn't available here (already
 * verified/expired, or the short-lived display cache missed), the customer
 * is pointed at the rider/SMS instead of being offered a self-service resend.
 */
export function OtpResendCard({ title, hint, phone, purpose, otp, whatsappShareLabel }: OtpResendCardProps) {
  const verified = otp?.status === 'verified';
  const expired = otp?.status === 'expired';
  const accent = purpose === 'pickup' ? color.tintBlueIcon : color.tintPurpleIcon;
  const accentBg = purpose === 'pickup' ? color.tintBlueBg : color.tintPurpleBg;

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
      }
    } catch {
      // Non-critical — the code is already shown/sent via SMS either way.
    }
  };

  return (
    <View style={styles.card}>
      <View style={styles.header}>
        <View style={[styles.iconBadge, { backgroundColor: accentBg }]}>
          <Icon name={purpose === 'pickup' ? 'cube-outline' : 'checkmark-done-outline'} size={16} color={accent} />
        </View>
        <Text style={styles.title}>{title}</Text>
        {verified ? (
          <View style={styles.verifiedPill}>
            <Icon name="checkmark-circle" size={13} color={color.success} />
            <Text style={styles.verifiedPillText}>Verified</Text>
          </View>
        ) : null}
      </View>

      {verified ? null : otp?.code ? (
        <>
          <View style={[styles.codeBox, { borderColor: accentBg }]}>
            <Text style={styles.code}>{otp.code}</Text>
          </View>
          <Text style={styles.hint}>{hint}</Text>
        </>
      ) : (
        <Text style={styles.body}>
          {expired
            ? 'This code has expired — ask your rider to generate a new one at the next step.'
            : phone
              ? `Sent via SMS to +91 ${maskPhone(phone)}. Only your rider can regenerate this code if it's lost.`
              : 'Sent via SMS. Only your rider can regenerate this code if it’s lost.'}
        </Text>
      )}

      {whatsappShareLabel && otp?.code && !verified ? (
        <Text style={styles.whatsappLink} onPress={onShareWhatsapp}>
          <Icon name="logo-whatsapp" size={14} color={color.success} /> {whatsappShareLabel}
        </Text>
      ) : null}
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
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: space[3],
    gap: space[2],
  },
  iconBadge: {
    width: 28,
    height: 28,
    borderRadius: radius.pill,
    alignItems: 'center',
    justifyContent: 'center',
  },
  title: {
    ...typography.h2,
    color: color.textPrimary,
    flex: 1,
  },
  verifiedPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: '#EAF7EE',
    borderRadius: radius.pill,
    paddingHorizontal: space[2],
    paddingVertical: 3,
  },
  verifiedPillText: {
    ...typography.caption,
    color: color.success,
  },
  body: {
    ...typography.body,
    color: color.textSecondary,
  },
  codeBox: {
    alignSelf: 'flex-start',
    borderWidth: 1.5,
    borderRadius: radius.md,
    borderStyle: 'dashed',
    paddingHorizontal: space[4],
    paddingVertical: space[2],
    marginBottom: space[2],
  },
  code: {
    ...typography.display,
    color: color.primary,
    letterSpacing: 6,
  },
  hint: {
    ...typography.caption,
    color: color.textSecondary,
  },
  whatsappLink: {
    ...typography.bodyStrong,
    color: color.success,
    marginTop: space[3],
  },
});
