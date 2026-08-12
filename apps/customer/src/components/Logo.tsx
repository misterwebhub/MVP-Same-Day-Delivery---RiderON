import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import Svg, { Path, Rect } from 'react-native-svg';
import { color } from '@rideron/design-tokens';

interface LogoProps {
  /** Pixel height of the mark; wordmark font size scales with it. Defaults to 32. */
  size?: number;
  /** 'light' = orange mark + white wordmark (for the navy splash/header); 'dark' = orange mark + navy wordmark (for white backgrounds). */
  variant?: 'light' | 'dark';
  /** Show only the box mark, no "RiderON" wordmark next to it. */
  markOnly?: boolean;
}

/**
 * In-code vector recreation of the RiderON mark (a delivery box with a
 * forward motion chevron) plus the "Rider" + "ON" wordmark, since no
 * exported logo asset file exists in the repo yet — see Logo.tsx's usage
 * sites for how to swap in a real PNG/SVG file once one is provided.
 */
export function Logo({ size = 32, variant = 'dark', markOnly = false }: LogoProps) {
  const wordColor = variant === 'light' ? color.textInverse : color.secondary;

  return (
    <View style={styles.row}>
      <Svg width={size} height={size} viewBox="0 0 48 48">
        <Rect x="4" y="4" width="40" height="40" rx="12" fill={color.secondary} />
        <Rect x="12" y="16" width="18" height="18" rx="3" fill={color.primary} />
        <Path d="M17 25 L26 25" stroke={color.textInverse} strokeWidth="2.5" strokeLinecap="round" />
        <Path d="M21 21 L26 25 L21 29" stroke={color.textInverse} strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" fill="none" />
        <Path d="M30 14 L38 22 L32 22 L36 30 L26 20 L32 20 Z" fill={color.primary} opacity={0.9} />
      </Svg>
      {!markOnly && (
        <Text style={[styles.wordmark, { fontSize: size * 0.62, color: wordColor }]}>
          Rider<Text style={{ color: color.primary }}>ON</Text>
        </Text>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  wordmark: {
    fontFamily: 'Poppins_600SemiBold',
    letterSpacing: 0.2,
  },
});
