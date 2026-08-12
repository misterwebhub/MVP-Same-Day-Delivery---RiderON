import type { TokenPair } from '@rideron/types';

/**
 * Deliberately environment-agnostic — this package must not depend on
 * `expo-secure-store` or any RN module so it can also run under plain
 * Node (tests, scripts). The app wires in a real implementation
 * (SecureStore-backed) at startup; see apps/customer/src/lib/tokenStorage.ts.
 */
export interface TokenStorage {
  getAccessToken(): Promise<string | null>;
  getRefreshToken(): Promise<string | null>;
  setTokens(tokens: TokenPair): Promise<void>;
  clearTokens(): Promise<void>;
}

/** In-memory fallback — useful for tests and as a safe default. Not persisted. */
export class InMemoryTokenStorage implements TokenStorage {
  private accessToken: string | null = null;
  private refreshToken: string | null = null;

  async getAccessToken(): Promise<string | null> {
    return this.accessToken;
  }

  async getRefreshToken(): Promise<string | null> {
    return this.refreshToken;
  }

  async setTokens(tokens: TokenPair): Promise<void> {
    this.accessToken = tokens.access_token;
    this.refreshToken = tokens.refresh_token;
  }

  async clearTokens(): Promise<void> {
    this.accessToken = null;
    this.refreshToken = null;
  }
}
