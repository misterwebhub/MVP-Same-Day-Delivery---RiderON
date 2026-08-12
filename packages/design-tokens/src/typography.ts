/**
 * Typography tokens — transcribed from docs/07-design-system.md ("Typography" section).
 *
 * Font families reference the @expo-google-fonts/poppins and @expo-google-fonts/inter
 * package naming convention (e.g. `Poppins_600SemiBold`), which the app loads via
 * `expo-font` at startup. Screens must reference `typography.*` tokens, never raw
 * font family strings or sizes.
 */
export const fontFamily = {
  poppinsSemiBold: 'Poppins_600SemiBold',
  poppinsMedium: 'Poppins_500Medium',
  interRegular: 'Inter_400Regular',
  interSemiBold: 'Inter_600SemiBold',
  interMedium: 'Inter_500Medium',
} as const;

export interface TypographyStyle {
  fontFamily: string;
  fontSize: number;
  lineHeight: number;
  textTransform?: 'uppercase';
  letterSpacing?: number;
}

export const typography = {
  display: {
    fontFamily: fontFamily.poppinsSemiBold,
    fontSize: 28,
    lineHeight: 36,
  },
  h1: {
    fontFamily: fontFamily.poppinsSemiBold,
    fontSize: 22,
    lineHeight: 28,
  },
  h2: {
    fontFamily: fontFamily.poppinsMedium,
    fontSize: 18,
    lineHeight: 24,
  },
  body: {
    fontFamily: fontFamily.interRegular,
    fontSize: 15,
    lineHeight: 22,
  },
  bodyStrong: {
    fontFamily: fontFamily.interSemiBold,
    fontSize: 15,
    lineHeight: 22,
  },
  caption: {
    fontFamily: fontFamily.interRegular,
    fontSize: 13,
    lineHeight: 18,
  },
  micro: {
    fontFamily: fontFamily.interMedium,
    fontSize: 11,
    lineHeight: 14,
    textTransform: 'uppercase',
  },
} satisfies Record<string, TypographyStyle>;

export type TypographyToken = keyof typeof typography;

/** Minimum readable size anywhere in the app, per doc 07's accessibility requirement. */
export const MIN_FONT_SIZE = 13;
