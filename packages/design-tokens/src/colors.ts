/**
 * Color tokens — transcribed from docs/07-design-system.md ("Color palette" table).
 * Semantic names only; never reference raw hex outside this file (per doc 07's
 * "tokens are structured so a dark theme can be added later without touching
 * component code" requirement).
 */
export const color = {
  primary: '#FF6A00',
  primaryDark: '#E85A00',
  primaryTint: '#FFF1E6',

  secondary: '#0A1B3D',
  secondaryTint: '#13284F',

  surface: '#FFFFFF',
  background: '#F5F6FA',

  textPrimary: '#0F172A',
  textSecondary: '#6B7280',
  textInverse: '#FFFFFF',

  border: '#E5E7EB',

  success: '#16A34A',
  warning: '#F59E0B',
  error: '#DC2626',
  info: '#2563EB',

  /** Soft tint + matching icon color pairs used for the Home screen's quick-action grid. */
  tintOrangeBg: '#FFE8D9',
  tintOrangeIcon: '#FF6A00',
  tintBlueBg: '#DCEAFF',
  tintBlueIcon: '#2563EB',
  tintGreenBg: '#DFF5E3',
  tintGreenIcon: '#16A34A',
  tintPurpleBg: '#EAE1FB',
  tintPurpleIcon: '#7C3AED',

  /** Card drop-shadow color — always used with low opacity, never solid. */
  shadow: '#0A1B3D',
} as const;

export type ColorToken = keyof typeof color;

/**
 * Status badge color mapping — per doc 07's "Status badge mapping" section.
 * Always pair with an icon + label in the UI; never color-only.
 */
export const statusBadgeColor = {
  BOOKED: color.info,
  RIDER_ASSIGNED: color.info,
  PICKED_UP: color.primary,
  IN_TRANSIT: color.primary,
  ARRIVED_DESTINATION: color.warning,
  WAITING_FOR_RECEIVER: color.warning,
  DELIVERED: color.success,
  COMPLETED: color.success,
  CANCELLED: color.error,
  FAILED_DELIVERY: color.error,
  REFUNDED: color.error,
} as const;

export type StatusBadgeKey = keyof typeof statusBadgeColor;
