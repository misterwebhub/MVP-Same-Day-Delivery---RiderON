export * from './colors';
export * from './typography';
export * from './spacing';

import { color, statusBadgeColor } from './colors';
import { fontFamily, typography, MIN_FONT_SIZE } from './typography';
import { space, radius, MIN_TOUCH_TARGET } from './spacing';

/** Convenience aggregate for components that want the whole token set at once. */
export const theme = {
  color,
  statusBadgeColor,
  fontFamily,
  typography,
  space,
  radius,
  MIN_FONT_SIZE,
  MIN_TOUCH_TARGET,
} as const;

export type Theme = typeof theme;
