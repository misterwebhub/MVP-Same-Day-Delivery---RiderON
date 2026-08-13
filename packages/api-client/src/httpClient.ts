import type { ApiErrorEnvelope, ApiSuccessEnvelope } from '@rideron/types';
import { ApiClientError } from './errors';
import type { TokenStorage } from './tokenStorage';
import { InMemoryTokenStorage } from './tokenStorage';

export interface ApiClientOptions {
  /** e.g. "http://10.0.2.2:8000/api/v1" (Android emulator) or "http://localhost:8000/api/v1" (web). */
  baseUrl: string;
  tokenStorage?: TokenStorage;
  /** Called once when a refresh attempt itself fails — app should route to the login screen. */
  onSessionExpired?: () => void;
  /** Overridable for tests; defaults to the global fetch. */
  fetchImpl?: typeof fetch;
}

/** React Native's shape for a picked image/file passed into a FormData field. */
export interface FormDataFile {
  uri: string;
  name: string;
  type: string;
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
  private readonly baseUrl: string;
  private readonly tokenStorage: TokenStorage;
  private readonly onSessionExpired?: () => void;
  private readonly fetchImpl: typeof fetch;
  private refreshInFlight: Promise<boolean> | null = null;

  constructor(options: ApiClientOptions) {
    this.baseUrl = options.baseUrl.replace(/\/+$/, '');
    this.tokenStorage = options.tokenStorage ?? new InMemoryTokenStorage();
    this.onSessionExpired = options.onSessionExpired;
    // Bind to globalThis: calling an unbound `fetch` reference as `this.fetchImpl(...)`
    // (i.e. as a method of this class instance) makes native fetch's internal branding
    // check fail with "TypeError: Failed to execute 'fetch' on 'Window': Illegal invocation",
    // since fetch requires its receiver to be the Window/WorkerGlobalScope object.
    this.fetchImpl = options.fetchImpl ?? fetch.bind(globalThis);
  }

  getTokenStorage(): TokenStorage {
    return this.tokenStorage;
  }

  async request<T>(path: string, options: RequestOptions = {}): Promise<T> {
    return this.doRequest<T>(path, options, /* isRetry */ false);
  }

  private async doRequest<T>(path: string, options: RequestOptions, isRetry: boolean): Promise<T> {
    const url = this.buildUrl(path, options.query);
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
          // Native platforms: React Native's FormData accepts {uri,name,type}
          // directly; the DOM lib types don't know that shape, hence the cast.
          form.append(key, value as unknown as Blob, value.name);
        }
      }
      requestBody = form;
    } else if (options.body !== undefined) {
      requestBody = JSON.stringify(options.body);
    }

    let response: Response;
    try {
      response = await this.fetchImpl(url, {
        method: options.method ?? 'GET',
        headers,
        body: requestBody,
      });
    } catch (networkError) {
      throw new ApiClientError(0, null, networkError instanceof Error ? networkError.message : 'Network request failed.');
    }

    const json = await this.safeParseJson(response);

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
