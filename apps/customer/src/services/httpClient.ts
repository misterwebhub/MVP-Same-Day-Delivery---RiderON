import { Platform } from 'react-native';
import { createApiClient } from '@rideron/api-client';
import { SecureTokenStorage } from './tokenStorage';

/**
 * Resolves the Laravel backend base URL for local development.
 *
 * Override at build time via an .env file (not committed) setting
 * `EXPO_PUBLIC_API_URL=http://<lan-ip>:8000/api/v1` — required when running
 * on a physical device, since `localhost` there means the device itself,
 * and `10.0.2.2` (the Android emulator's host-loopback alias) doesn't apply
 * to a real phone either.
 */
function resolveBaseUrl(): string {
  const override = process.env.EXPO_PUBLIC_API_URL;
  if (override) {
    return override;
  }

  if (Platform.OS === 'android') {
    // 10.0.2.2 is the Android emulator's alias for the host machine's localhost.
    return 'http://10.0.2.2:8000/api/v1';
  }

  // iOS simulator and web dev server both reach the host machine via localhost.
  return 'http://localhost:8000/api/v1';
}

let onSessionExpired: (() => void) | undefined;

/** Registered once by the navigation shell (Task #23) so an expired refresh token routes back to Login. */
export function setSessionExpiredHandler(handler: () => void): void {
  onSessionExpired = handler;
}

export const apiClient = createApiClient({
  baseUrl: resolveBaseUrl(),
  tokenStorage: new SecureTokenStorage(),
  onSessionExpired: () => onSessionExpired?.(),
});
