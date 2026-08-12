import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';
import type { TokenPair } from '@rideron/types';
import type { TokenStorage } from '@rideron/api-client';

/**
 * expo-secure-store backed TokenStorage — persists the Sanctum access token
 * and opaque refresh token in the platform keychain. Mirrors
 * apps/customer/src/services/tokenStorage.ts; a separate app bundle id means
 * this never collides with the customer app's stored session on the same
 * device, so the key names don't need a partner-specific prefix.
 *
 * As of Expo SDK 57, expo-secure-store ships NO web implementation (its web
 * module is a literal `export default {}`), so calling any method on web
 * throws "ExpoSecureStore.default.getValueWithKeyAsync is not a function".
 * We branch on Platform.OS and talk to window.localStorage directly on web,
 * for local dev/verification of the web build only.
 */
const ACCESS_TOKEN_KEY = 'rideron.access_token';
const REFRESH_TOKEN_KEY = 'rideron.refresh_token';

function webGet(key: string): string | null {
  return typeof window === 'undefined' ? null : window.localStorage.getItem(key);
}

function webSet(key: string, value: string): void {
  if (typeof window !== 'undefined') window.localStorage.setItem(key, value);
}

function webDelete(key: string): void {
  if (typeof window !== 'undefined') window.localStorage.removeItem(key);
}

export class SecureTokenStorage implements TokenStorage {
  async getAccessToken(): Promise<string | null> {
    if (Platform.OS === 'web') return webGet(ACCESS_TOKEN_KEY);
    return SecureStore.getItemAsync(ACCESS_TOKEN_KEY);
  }

  async getRefreshToken(): Promise<string | null> {
    if (Platform.OS === 'web') return webGet(REFRESH_TOKEN_KEY);
    return SecureStore.getItemAsync(REFRESH_TOKEN_KEY);
  }

  async setTokens(tokens: TokenPair): Promise<void> {
    if (Platform.OS === 'web') {
      webSet(ACCESS_TOKEN_KEY, tokens.access_token);
      webSet(REFRESH_TOKEN_KEY, tokens.refresh_token);
      return;
    }
    await Promise.all([
      SecureStore.setItemAsync(ACCESS_TOKEN_KEY, tokens.access_token),
      SecureStore.setItemAsync(REFRESH_TOKEN_KEY, tokens.refresh_token),
    ]);
  }

  async clearTokens(): Promise<void> {
    if (Platform.OS === 'web') {
      webDelete(ACCESS_TOKEN_KEY);
      webDelete(REFRESH_TOKEN_KEY);
      return;
    }
    await Promise.all([
      SecureStore.deleteItemAsync(ACCESS_TOKEN_KEY),
      SecureStore.deleteItemAsync(REFRESH_TOKEN_KEY),
    ]);
  }
}
