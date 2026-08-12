import AsyncStorage from '@react-native-async-storage/async-storage';

/**
 * Non-sensitive local flag storage — AsyncStorage (not SecureStore/MMKV) per
 * docs/08's `services/storage.ts # MMKV/AsyncStorage wrapper` note. MMKV needs
 * a native config plugin that doesn't work in Expo Go/web, so AsyncStorage is
 * used for simple flags; auth tokens stay in SecureStore (services/tokenStorage.ts).
 */
const KEYS = {
  onboardingSeen: 'rideron.onboardingSeen',
  displayLanguage: 'rideron.displayLanguage',
} as const;

export type DisplayLanguage = 'en' | 'hi';

export const storage = {
  async getOnboardingSeen(): Promise<boolean> {
    const value = await AsyncStorage.getItem(KEYS.onboardingSeen);
    return value === 'true';
  },

  async setOnboardingSeen(): Promise<void> {
    await AsyncStorage.setItem(KEYS.onboardingSeen, 'true');
  },

  /** Device-local UI language preference. There's no in-app translation catalog
   * yet, so this only persists the choice (and best-effort syncs it to the
   * backend's `preferred_language` profile field) — it does not retranslate
   * screen text. Read this instead of pretending a language switch is a no-op. */
  async getDisplayLanguage(): Promise<DisplayLanguage> {
    const value = await AsyncStorage.getItem(KEYS.displayLanguage);
    return value === 'hi' ? 'hi' : 'en';
  },

  async setDisplayLanguage(lang: DisplayLanguage): Promise<void> {
    await AsyncStorage.setItem(KEYS.displayLanguage, lang);
  },
};
