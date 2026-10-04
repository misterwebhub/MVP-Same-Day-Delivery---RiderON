import React, { useEffect } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { color, space, typography } from '@rideron/design-tokens';
import { storage } from '../../../services/storage';
import { Logo } from '../../../components/Logo';
import { Icon } from '../../../components/Icon';
import { useSafeBottomPadding } from '../../../hooks/useSafeBottomPadding';
import type { AuthStackParamList } from '../../../navigation/types';

type Props = NativeStackScreenProps<AuthStackParamList, 'Splash'>;

/**
 * Boot screen while AuthProvider resolves stored-session state (see
 * useAuth.tsx) — this Splash is a UX gate on top of that, deciding whether
 * a not-yet-authenticated user needs Onboarding or can go straight to Login.
 */
export function Splash({ navigation }: Props) {
  const bottomPadding = useSafeBottomPadding(space[8]);

  useEffect(() => {
    let cancelled = false;
    storage.getOnboardingSeen().then((seen) => {
      if (cancelled) return;
      navigation.replace(seen ? 'Login' : 'Onboarding');
    });
    return () => {
      cancelled = true;
    };
  }, [navigation]);

  return (
    <View style={styles.container}>
      <View style={styles.center}>
        <Logo size={56} variant="light" markOnly />
        <Text style={styles.wordmark}>
          Rider<Text style={{ color: color.primary }}>ON</Text>
        </Text>
        <Text style={styles.tagline}>FAST AND SAME DAY DELIVERY</Text>

        <View style={styles.route}>
          <Icon name="location" size={18} color={color.primary} />
          <View style={styles.routeDashes}>
            {Array.from({ length: 10 }).map((_, i) => (
              <View key={i} style={styles.dash} />
            ))}
          </View>
          <Icon name="location" size={18} color={color.primary} />
        </View>
        <View style={styles.routeLabels}>
          <Text style={styles.routeLabelText}>STATION A</Text>
          <Text style={styles.routeLabelText}>STATION B</Text>
        </View>
      </View>

      <Text style={[styles.footer, { bottom: bottomPadding }]}>Delivering Trust, From Station to Station</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: color.secondary,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: space[6],
  },
  center: {
    alignItems: 'center',
  },
  wordmark: {
    ...typography.display,
    fontSize: 34,
    color: color.textInverse,
    marginTop: space[4],
  },
  tagline: {
    ...typography.micro,
    color: color.textInverse,
    opacity: 0.6,
    letterSpacing: 2,
    marginTop: space[1],
  },
  route: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: space[8],
    width: 220,
  },
  routeDashes: {
    flex: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginHorizontal: space[2],
  },
  dash: {
    width: 4,
    height: 2,
    borderRadius: 1,
    backgroundColor: color.textInverse,
    opacity: 0.4,
  },
  routeLabels: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    width: 220,
    marginTop: space[1],
  },
  routeLabelText: {
    ...typography.micro,
    color: color.textInverse,
    opacity: 0.5,
  },
  footer: {
    position: 'absolute',
    bottom: space[8],
    ...typography.bodyStrong,
    color: color.textInverse,
    opacity: 0.85,
    textAlign: 'center',
  },
});
