/**
 * Spacing, radius and sizing tokens — transcribed from docs/07-design-system.md
 * ("Spacing & sizing" section). 4px base unit.
 *
 * NOTE: `space` has numeric keys — always access via bracket notation
 * (`space[4]`), never dot notation (`space.4` is a JS syntax error: after a
 * `.` the parser expects an identifier, not a numeric literal).
 */
export const space = {
  1: 4,
  2: 8,
  3: 12,
  4: 16,
  5: 20,
  6: 24,
  8: 32,
} as const;

export type SpaceToken = keyof typeof space;

export const radius = {
  sm: 8,
  md: 12,
  lg: 16,
  pill: 999,
} as const;

export type RadiusToken = keyof typeof radius;

/** Minimum touch target size (dp) for all interactive elements. */
export const MIN_TOUCH_TARGET = 48;
