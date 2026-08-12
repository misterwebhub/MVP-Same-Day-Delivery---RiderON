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

export interface RequestOptions {
  method?: 'GET' | 'POST' | 'PATCH' | 'DELETE';
  body?: unknown;
  query?: Record<string, string | number | boolean | undefined | null>;
  /** Attaches the Idempotency-Key header — required by mutating endpoints behind the `idempotent` middleware. */
  idempotencyKey?: string;
  /** Skip attaching Authorization + skip the refresh-and-retry dance (login/otp endpoints). */
  skipAuth?: boolean;
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
    if (options.idempotencyKey) {
      headers['Idempotency-Key'] = options.idempotencyKey;
    }
    if (!options.skipAuth) {
      const accessToken = await this.tokenStorage.getAccessToken();
      if (accessToken) {
        headers.Authorization = `Bearer ${accessToken}`;
      }
    }

    let response: Response;
    try {
      response = await this.fetchImpl(url, {
        method: options.method ?? 'GET',
        headers,
        body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
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
