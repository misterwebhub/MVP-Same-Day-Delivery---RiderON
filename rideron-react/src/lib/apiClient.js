import { createApiClient } from '@rideron/api-client';
import { LocalStorageTokenStorage } from './tokenStorage';

/**
 * Live backend by default (see rideron-react/.env). Override for local dev
 * against `php artisan serve` with VITE_API_URL=http://127.0.0.1:8000/api/v1.
 * Matches apps/customer's DEFAULT_BASE_URLS.production.
 */
const FALLBACK_BASE_URL = 'https://rideron.impixoexports.com/api/v1';

function baseUrl() {
  return import.meta.env.VITE_API_URL || FALLBACK_BASE_URL;
}

let onSessionExpired;

/** Registered once by AuthProvider so an expired refresh token routes back to Login. */
export function setSessionExpiredHandler(handler) {
  onSessionExpired = handler;
}

// No custom `uploadFile` override needed here (unlike apps/customer) — the
// HttpClient's default fetch+FormData path already handles blob:/data: URIs
// correctly, which is exactly what `URL.createObjectURL(file)` on a browser
// <input type="file"> File produces.
export const apiClient = createApiClient({
  baseUrl: baseUrl(),
  tokenStorage: new LocalStorageTokenStorage(),
  onSessionExpired: () => onSessionExpired?.(),
});
