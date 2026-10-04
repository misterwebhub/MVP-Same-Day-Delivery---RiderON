import type { ApiErrorEnvelope, ApiSuccessEnvelope } from '@rideron/types';
import { ApiClientError } from './errors';
import type { TokenStorage } from './tokenStorage';
import { InMemoryTokenStorage } from './tokenStorage';

export interface ApiClientOptions {
  /** e.g. "http://10.0.2.2:8000/api/v1" (Android emulator) or "http://localhost:8000/api/v1" (web).
   * Used immediately (synchronously) for any request that goes out before
   * `resolveBaseUrl` (if provided) finishes resolving — so this should be a
   * sane, always-available fallback, not a placeholder. */
  baseUrl: string;
  /** Optional async override, resolved once and memoized on first request.
   * Lets the app look up its base URL from a remote config source (e.g. a
   * small hosted JSON endpoint) instead of baking a fixed host into the
   * build. On failure/rejection the client silently keeps using `baseUrl`. */
  resolveBaseUrl?: () => Promise<string>;
  tokenStorage?: TokenStorage;
  /** Called once when a refresh attempt itself fails — app should route to the login screen. */
  onSessionExpired?: () => void;
  /** Overridable for tests; defaults to the global fetch. */
  fetchImpl?: typeof fetch;
  /**
   * Optional override for multipart/form-data uploads that include a local
   * file URI (photo evidence endpoints). Must resolve to a real `Response`
   * (e.g. `new Response(body, { status, headers })`).
   *
   * Why this exists: React Native's bridge FormData path — appending a
   * `{uri,name,type}` object and letting the native Networking module build
   * the multipart body — crashes natively with "Unsupported FormDataPart" on
   * the New Architecture (observed on RN 0.86 with newArchEnabled). Native
   * apps should inject a file-system-based uploader here (e.g. expo-file-system's
   * `File.upload()`) that encodes the multipart body natively instead of going
   * through that bridge. Used only when `formData` contains an entry with a
   * real local file URI (not `blob:`/`data:`, which stay on the fetch+FormData
   * path below since those already resolve to real Blobs). Falls back to
   * fetch+FormData when not provided.
   */
  uploadFile?: (params: UploadFileParams) => Promise<Response>;
}

/** React Native's shape for a picked image/file passed into a FormData field. */
export interface FormDataFile {
  uri: string;
  name: string;
  type: string;
}

export interface UploadFileParams {
  url: string;
  method: string;
  headers: Record<string, string>;
  /** Non-file fields from the same `formData` (e.g. GPS coords) — sent as regular multipart text fields. */
  fields: Record<string, string>;
  fileField: string;
  file: FormDataFile;
}

export interface RequestOptions {
  method?: 'GET' | 'POST' | 'PATCH' | 'DELETE';
  body?: unknown;
  query?: Record<string, string | number | boolean | undefined | null>;
  /** Attaches the Idempotency-Key header — required by mutating endpoints behind the `idempotent` middleware. */
  idempotencyKey?: string;
  /** Skip attaching Authorization + skip the refresh-and-retry dance (login/otp endpoints). */
  skipAuth?: boolean;
  /** Multipart upload (photo evidence endpoints). Mutually exclusive with `body` —
   * the runtime sets its own Content-Type boundary, so we must not set one manually. */
  formData?: Record<string, FormDataFile | string>;
}

/**
 * Thin typed fetch wrapper around the Laravel API's {success,data,message}
 * envelope (app/Http/Responses/ApiResponse.php) and error envelope
 * (app/Exceptions/ApiExceptionRenderer.php).
 *
 * Handles: Authorization header injection, single-flight access-token
 * refresh-and-retry on a 401 UNAUTHENTICATED response, and Idempotency-Key
 * header passthrough. Does not know about individual endpoints — see
 * resources.ts for the typed endpoint methods built on top of `request()`.
 */
export class HttpClient {
  private baseUrl: string;
  private readonly resolveBaseUrlFn?: () => Promise<string>;
  private baseUrlReady: Promise<void> | null = null;
  private readonly tokenStorage: TokenStorage;
  private readonly onSessionExpired?: () => void;
  private readonly fetchImpl: typeof fetch;
  private readonly uploadFileImpl?: (params: UploadFileParams) => Promise<Response>;
  private refreshInFlight: Promise<boolean> | null = null;

  constructor(options: ApiClientOptions) {
    this.baseUrl = options.baseUrl.replace(/\/+$/, '');
    this.resolveBaseUrlFn = options.resolveBaseUrl;
    this.tokenStorage = options.tokenStorage ?? new InMemoryTokenStorage();
    this.onSessionExpired = options.onSessionExpired;
    // Bind to globalThis: calling an unbound `fetch` reference as `this.fetchImpl(...)`
    // (i.e. as a method of this class instance) makes native fetch's internal branding
    // check fail with "TypeError: Failed to execute 'fetch' on 'Window': Illegal invocation",
    // since fetch requires its receiver to be the Window/WorkerGlobalScope object.
    this.fetchImpl = options.fetchImpl ?? fetch.bind(globalThis);
    this.uploadFileImpl = options.uploadFile;
  }

  getTokenStorage(): TokenStorage {
    return this.tokenStorage;
  }

  async request<T>(path: string, options: RequestOptions = {}): Promise<T> {
    return this.doRequest<T>(path, options, /* isRetry */ false);
  }

  /** Resolved once and memoized — every subsequent request reuses the same
   * settled promise, so the remote lookup only ever happens a single time
   * per app session. A rejection is swallowed here (not surfaced to the
   * caller of `request()`) so the constructor's synchronous `baseUrl`
   * fallback silently keeps serving requests if the remote config is
   * unreachable. */
  private ensureBaseUrl(): Promise<void> {
    if (!this.resolveBaseUrlFn) {
      return Promise.resolve();
    }
    if (!this.baseUrlReady) {
      this.baseUrlReady = this.resolveBaseUrlFn()
        .then((resolved) => {
          if (resolved) {
            this.baseUrl = resolved.replace(/\/+$/, '');
          }
        })
        .catch(() => undefined);
    }
    return this.baseUrlReady;
  }

  private async doRequest<T>(path: string, options: RequestOptions, isRetry: boolean): Promise<T> {
    await this.ensureBaseUrl();
    const url = this.buildUrl(path, options.query);
    // TEMP DEBUG TRACING — remove after diagnosing request flow.
    const __t0 = Date.now();
    // eslint-disable-next-line no-console
    console.log(`[HTTP →] ${options.method ?? 'GET'} ${url}`, options.body ?? options.formData ?? '');
    const headers: Record<string, string> = { Accept: 'application/json' };

    if (options.body !== undefined) {
      headers['Content-Type'] = 'application/json';
    }
    // formData: deliberately no Content-Type header — fetch/RN sets the multipart
    // boundary itself only when it builds the body, not when we set the header.
    if (options.idempotencyKey) {
      headers['Idempotency-Key'] = options.idempotencyKey;
    }
    if (!options.skipAuth) {
      const accessToken = await this.tokenStorage.getAccessToken();
      if (accessToken) {
        headers.Authorization = `Bearer ${accessToken}`;
      }
    }

    // A "real" local file (not blob:/data:, which already resolve to actual Blobs on
    // web) present in formData — when there's an injected uploadFileImpl, this whole
    // request is delegated to it instead of going through fetch+FormData at all. See
    // the ApiClientOptions.uploadFile doc comment for why (RN New Architecture bridge crash).
    const nativeFileEntry = options.formData
      ? Object.entries(options.formData).find(
          (entry): entry is [string, FormDataFile] =>
            typeof entry[1] !== 'string' && !entry[1].uri.startsWith('blob:') && !entry[1].uri.startsWith('data:'),
        )
      : undefined;

    let response: Response;
    if (nativeFileEntry && this.uploadFileImpl) {
      const [fileField, file] = nativeFileEntry;
      const fields: Record<string, string> = {};
      for (const [key, value] of Object.entries(options.formData!)) {
        if (typeof value === 'string') fields[key] = value;
      }
      try {
        response = await this.uploadFileImpl({ url, method: options.method ?? 'POST', headers, fields, fileField, file });
      } catch (networkError) {
        // eslint-disable-next-line no-console
        console.log(`[HTTP ✗] ${url} upload threw`, networkError);
        throw new ApiClientError(0, null, networkError instanceof Error ? networkError.message : 'Network request failed.');
      }
    } else {
      let requestBody: BodyInit | undefined;
      if (options.formData) {
        const form = new FormData();
        for (const [key, value] of Object.entries(options.formData)) {
          if (typeof value === 'string') {
            form.append(key, value);
          } else if (value.uri.startsWith('blob:') || value.uri.startsWith('data:')) {
            // On web, expo-image-picker returns a blob:/data: URI, not a real file —
            // and react-native-web's FormData is the browser's real FormData, which
            // (unlike React Native's own FormData polyfill) does NOT special-case a
            // plain {uri,name,type} object. Appending it directly throws
            // "parameter 2 is not of type 'Blob'" synchronously, before fetch() is
            // even called — which surfaces as a raw (non-ApiClientError) exception
            // in the caller, i.e. the generic "Could not upload..." fallback message.
            // Resolve the URI to a real Blob first so the browser's FormData accepts it.
            const blob = await (await this.fetchImpl(value.uri)).blob();
            form.append(key, blob, value.name);
          } else {
            // No uploadFileImpl was injected for this platform (e.g. tests) — fall
            // back to RN's native FormData bridge. This is the path that's unreliable
            // on the New Architecture; apps should prefer wiring up `uploadFile`.
            form.append(key, value as unknown as Blob, value.name);
          }
        }
        requestBody = form;
      } else if (options.body !== undefined) {
        requestBody = JSON.stringify(options.body);
      }

      try {
        response = await this.fetchImpl(url, {
          method: options.method ?? 'GET',
          headers,
          body: requestBody,
        });
      } catch (networkError) {
        // eslint-disable-next-line no-console
        console.log(`[HTTP ✗] ${url} fetch threw`, networkError);
        throw new ApiClientError(0, null, networkError instanceof Error ? networkError.message : 'Network request failed.');
      }
    }

    const json = await this.safeParseJson(response);
    // eslint-disable-next-line no-console
    console.log(`[HTTP ←] ${response.status} ${url} (${Date.now() - __t0}ms)`, json);

    if (response.ok) {
      return (json as ApiSuccessEnvelope<T>).data;
    }

    const envelope = json as ApiErrorEnvelope | null;

    // Single-flight refresh-and-retry on 401, exactly once, only for authenticated requests.
    if (response.status === 401 && !options.skipAuth && !isRetry) {
      const refreshed = await this.refreshAccessToken();
      if (refreshed) {
        return this.doRequest<T>(path, options, /* isRetry */ true);
      }
      await this.tokenStorage.clearTokens();
      this.onSessionExpired?.();
    }

    throw new ApiClientError(response.status, envelope, `Request failed with status ${response.status}.`);
  }

  private async refreshAccessToken(): Promise<boolean> {
    if (this.refreshInFlight) {
      return this.refreshInFlight;
    }

    this.refreshInFlight = (async () => {
      const refreshToken = await this.tokenStorage.getRefreshToken();
      if (!refreshToken) {
        return false;
      }

      try {
        const data = await this.doRequest<{ access_token: string; refresh_token: string }>(
          '/auth/refresh',
          { method: 'POST', body: { refresh_token: refreshToken }, skipAuth: true },
          /* isRetry */ true,
        );
        await this.tokenStorage.setTokens(data);
        return true;
      } catch {
        return false;
      }
    })();

    try {
      return await this.refreshInFlight;
    } finally {
      this.refreshInFlight = null;
    }
  }

  private buildUrl(path: string, query?: RequestOptions['query']): string {
    const url = new URL(`${this.baseUrl}${path.startsWith('/') ? path : `/${path}`}`);
    if (query) {
      for (const [key, value] of Object.entries(query)) {
        if (value !== undefined && value !== null) {
          url.searchParams.set(key, String(value));
        }
      }
    }
    return url.toString();
  }

  private async safeParseJson(response: Response): Promise<unknown> {
    const text = await response.text();
    if (!text) {
      return null;
    }
    try {
      return JSON.parse(text);
    } catch {
      return null;
    }
  }
}
