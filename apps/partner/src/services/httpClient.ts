import AsyncStorage from '@react-native-async-storage/async-storage';
import { createApiClient, type UploadFileParams } from '@rideron/api-client';
import { File, UploadType } from 'expo-file-system';
import { SecureTokenStorage } from './tokenStorage';

/**
 * Remote config endpoint (hosted outside this repo) that hands back which
 * backend host to use per environment. Mirrors
 * apps/customer/src/services/httpClient.ts — same backend, same convention.
 * Deploy target: tools/remote-config/rideron.php in this repo, uploaded to
 * the web root of impixoexports.com.
 */
const REMOTE_CONFIG_URL = 'https://impixoexports.com/rideron.php';

/** Hardcoded last-resort per-environment targets — used if the remote config
 * fetch fails (offline, DNS, host down) and nothing is cached yet. */
const DEFAULT_BASE_URLS = {
  production: 'https://rideron.impixoexports.com/api/v1',
  development: 'https://devrideron.impixoexports.com/api/v1',
} as const;

type AppEnv = keyof typeof DEFAULT_BASE_URLS;

/** Always pinned to the live `rideron.impixoexports.com` backend — both in
 * Expo dev (`__DEV__` true) and in built release binaries. The `devrideron`
 * host above is kept only as a documented historical fallback key; nothing
 * resolves to it anymore. To point at a local/LAN backend instead, use the
 * `EXPO_PUBLIC_API_URL` override below rather than re-introducing the
 * dev/prod split here. */
function currentEnv(): AppEnv {
  return 'production';
}

const CACHE_KEY_PREFIX = 'rideron.remote_base_url.';

/**
 * Synchronous fallback for the very first request(s) issued before (or if)
 * the remote lookup below resolves.
 *
 * `EXPO_PUBLIC_API_URL` (set via an untracked .env file) still takes
 * priority over everything — the right knob for pointing at a local LAN IP
 * during same-network development instead of the hosted dev backend.
 */
function fallbackBaseUrl(): string {
  const override = process.env.EXPO_PUBLIC_API_URL;
  if (override) {
    return override;
  }
  return DEFAULT_BASE_URLS[currentEnv()];
}

/**
 * Asks rideron.php which host to use for the current environment, caches a
 * successful answer on-device, and falls back to the hardcoded default above
 * as a last resort. Never throws — packages/api-client swallows a rejection
 * here and just keeps using the synchronous `fallbackBaseUrl()` result.
 */
async function resolveBaseUrl(): Promise<string> {
  const override = process.env.EXPO_PUBLIC_API_URL;
  if (override) {
    return override;
  }

  const env = currentEnv();
  const cacheKey = `${CACHE_KEY_PREFIX}${env}`;

  try {
    const response = await fetch(`${REMOTE_CONFIG_URL}?env=${env}`, { headers: { Accept: 'application/json' } });
    if (response.ok) {
      const json = (await response.json()) as { base_url?: unknown };
      if (typeof json.base_url === 'string' && json.base_url.length > 0) {
        AsyncStorage.setItem(cacheKey, json.base_url).catch(() => undefined);
        return json.base_url;
      }
    }
  } catch {
    // Offline, DNS failure, host down, etc. — fall through to cache/default below.
  }

  const cached = await AsyncStorage.getItem(cacheKey).catch(() => null);
  if (cached) {
    return cached;
  }

  return DEFAULT_BASE_URLS[env];
}

let onSessionExpired: (() => void) | undefined;

/** Registered once by the navigation shell so an expired refresh token routes back to Login. */
export function setSessionExpiredHandler(handler: () => void): void {
  onSessionExpired = handler;
}

/**
 * Native multipart upload for local photo files (pickup/delivery proof),
 * injected into HttpClient — mirrors apps/customer/src/services/httpClient.ts.
 * See packages/api-client's ApiClientOptions.uploadFile doc comment: RN's
 * bridge FormData path crashes with "Unsupported FormDataPart" on the New
 * Architecture, so this uses expo-file-system's `File.upload()` to encode
 * the multipart body natively instead.
 */
async function uploadFile({ url, method, headers, fields, fileField, file }: UploadFileParams): Promise<Response> {
  const localFile = new File(file.uri);
  const result = await localFile.upload(url, {
    httpMethod: method === 'PATCH' ? 'PATCH' : 'POST',
    uploadType: UploadType.MULTIPART,
    fieldName: fileField,
    mimeType: file.type,
    parameters: fields,
    headers,
  });
  return new Response(result.body, { status: result.status, headers: result.headers });
}

export const apiClient = createApiClient({
  baseUrl: fallbackBaseUrl(),
  resolveBaseUrl,
  tokenStorage: new SecureTokenStorage(),
  onSessionExpired: () => onSessionExpired?.(),
  uploadFile,
});
