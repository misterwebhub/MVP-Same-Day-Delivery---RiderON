import React, { useRef, useState } from 'react';
import {
  Dimensions,
  NativeScrollEvent,
  NativeSyntheticEvent,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { color, radius, space, typography } from '@rideron/design-tokens';
import { Button } from '../../../components/Button';
import { useSafeBottomPadding } from '../../../hooks/useSafeBottomPadding';
import { storage } from '../../../services/storage';
import type { AuthStackParamList } from '../../../navigation/types';

type Props = NativeStackScreenProps<AuthStackParamList, 'Onboarding'>;

const SLIDES = [
  {
    emoji: '⚡',
    title: 'Fast pickups',
    body: 'Book a parcel run between Kanpur and Lucknow in minutes — pick a route, a slot, and go.',
  },
  {
    emoji: '🛡️',
    title: 'Safe in transit',
    body: 'Every pickup and delivery is confirmed with an OTP, so your parcel only changes hands with your say-so.',
  },
  {
    emoji: '✅',
    title: 'Trusted riders',
    body: 'Orders are matched to a verified rider — accepted by them or assigned by our team — before payment is collected.',
  },
];

const { width: SCREEN_WIDTH } = Dimensions.get('window');

/** 3-slide intro carousel — FAST / SAFE / TRUSTED messaging, per the user's Phase 1 brief. */
export function Onboarding({ navigation }: Props) {
  const scrollRef = useRef<ScrollView>(null);
  const [index, setIndex] = useState(0);
  const bottomPadding = useSafeBottomPadding(space[8]);

  const finish = async () => {
    await storage.setOnboardingSeen();
    navigation.replace('Login');
  };

  const goNext = () => {
    if (index < SLIDES.length - 1) {
      const next = index + 1;
      scrollRef.current?.scrollTo({ x: next * SCREEN_WIDTH, animated: true });
      setIndex(next);
    } else {
      finish();
    }
  };

  const onMomentumScrollEnd = (e: NativeSyntheticEvent<NativeScrollEvent>) => {
    const newIndex = Math.round(e.nativeEvent.contentOffset.x / SCREEN_WIDTH);
    setIndex(newIndex);
  };

  return (
    <View style={styles.container}>
      <View style={styles.skipRow}>
        <Text style={styles.skipText} onPress={finish}>
          Skip
        </Text>
      </View>

      <ScrollView
        ref={scrollRef}
        horizontal
        pagingEnabled
        showsHorizontalScrollIndicator={false}
        onMomentumScrollEnd={onMomentumScrollEnd}
      >
        {SLIDES.map((slide) => (
          <View key={slide.title} style={[styles.slide, { width: SCREEN_WIDTH }]}>
            <Text style={styles.emoji}>{slide.emoji}</Text>
            <Text style={styles.title}>{slide.title}</Text>
            <Text style={styles.body}>{slide.body}</Text>
          </View>
        ))}
      </ScrollView>

      <View style={styles.dots}>
        {SLIDES.map((slide, i) => (
          <View key={slide.title} style={[styles.dot, i === index && styles.dotActive]} />
        ))}
      </View>

      <View style={[styles.footer, { paddingBottom: bottomPadding }]}>
        <Button title={index === SLIDES.length - 1 ? 'Get started' : 'Next'} onPress={goNext} />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: color.background,
  },
  skipRow: {
    alignItems: 'flex-end',
    paddingHorizontal: space[6],
    paddingTop: space[6],
  },
  skipText: {
    ...typography.bodyStrong,
    color: color.textSecondary,
    padding: space[2],
  },
  slide: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: space[8],
  },
  emoji: {
    fontSize: 64,
    marginBottom: space[6],
  },
  title: {
    ...typography.h1,
    color: color.textPrimary,
    textAlign: 'center',
    marginBottom: space[3],
  },
  body: {
    ...typography.body,
    color: color.textSecondary,
    textAlign: 'center',
  },
  dots: {
    flexDirection: 'row',
    justifyContent: 'center',
    gap: space[2],
    marginBottom: space[6],
  },
  dot: {
    width: 8,
    height: 8,
    borderRadius: radius.pill,
    backgroundColor: color.border,
  },
  dotActive: {
    backgroundColor: color.primary,
    width: 20,
  },
  footer: {
    paddingHorizontal: space[6],
    paddingBottom: space[8],
  },
});
