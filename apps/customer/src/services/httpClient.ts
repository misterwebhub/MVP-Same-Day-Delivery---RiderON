import AsyncStorage from '@react-native-async-storage/async-storage';
import { createApiClient, type UploadFileParams } from '@rideron/api-client';
import { File, UploadType } from 'expo-file-system';
import { SecureTokenStorage } from './tokenStorage';

/**
 * Remote config endpoint (hosted outside this repo, on the same account as
 * the production/dev domains below) that hands back which backend host to
 * use per environment. Deploy target: tools/remote-config/rideron.php in
 * this repo, uploaded to the web root of impixoexports.com. Lets the actual
 * backend host change (redeploy, new domain, swapped dev box) without a new
 * app build — the app just re-asks on next launch.
 */
const REMOTE_CONFIG_URL = 'https://impixoexports.com/rideron.php';

/** Hardcoded last-resort per-environment targets — used if the remote config
 * fetch fails (offline, DNS, host down) and nothing is cached yet. */
const DEFAULT_BASE_URLS = {
  production: 'https://rideron.impixoexports.com/api/v1',
  development: 'https://devrideron.impixoexports.com/api/v1',
} as const;

type AppEnv = keyof typeof DEFAULT_BASE_URLS;

/** `__DEV__` is Expo/Metro's dev-vs-release flag, true for `expo start` /
 * Expo Go, false for a built release binary — matches how the two hosted
 * backends are meant to be split (devrideron for daily dev, rideron for
 * shipped builds). */
function currentEnv(): AppEnv {
  return __DEV__ ? 'development' : 'production';
}

const CACHE_KEY_PREFIX = 'rideron.remote_base_url.';

/**
 * Synchronous fallback for the very first request(s) issued before (or if)
 * the remote lookup below resolves — the HttpClient uses this immediately
 * and swaps in the remote/cached value once available (packages/api-client's
 * `resolveBaseUrl` option).
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
 * successful answer on-device (survives app restarts, used if a later
 * lookup fails), and falls back to the hardcoded default above as a last
 * resort. Never throws — packages/api-client swallows a rejection here and
 * just keeps using the synchronous `fallbackBaseUrl()` result.
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
        // eslint-disable-next-line no-console
        console.log(`[BaseURL] remote-config resolved: ${json.base_url} (env=${env})`);
        return json.base_url;
      }
    }
    // eslint-disable-next-line no-console
    console.log(`[BaseURL] remote-config responded but no usable base_url (status=${response.status})`);
  } catch (e) {
    // Offline, DNS failure, host down, etc. — fall through to cache/default below.
    // eslint-disable-next-line no-console
    console.log('[BaseURL] remote-config fetch failed', e);
  }

  const cached = await AsyncStorage.getItem(cacheKey).catch(() => null);
  if (cached) {
    // eslint-disable-next-line no-console
    console.log(`[BaseURL] using cached base_url: ${cached}`);
    return cached;
  }

  // eslint-disable-next-line no-console
  console.log(`[BaseURL] using hardcoded default: ${DEFAULT_BASE_URLS[env]}`);
  return DEFAULT_BASE_URLS[env];
}

let onSessionExpired: (() => void) | undefined;

/** Registered once by the navigation shell (Task #23) so an expired refresh token routes back to Login. */
export function setSessionExpiredHandler(handler: () => void): void {
  onSessionExpired = handler;
}

/**
 * Native multipart upload for local photo files, injected into HttpClient
 * (see packages/api-client's ApiClientOptions.uploadFile doc comment).
 *
 * Bypasses RN's bridge FormData path — appending a `{uri,name,type}` object
 * and letting the native Networking module build the multipart body crashes
 * with "Unsupported FormDataPart" on the New Architecture (newArchEnabled,
 * RN 0.86). expo-file-system's `File.upload()` encodes/streams the multipart
 * body natively instead, so that bridge path is never hit.
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
