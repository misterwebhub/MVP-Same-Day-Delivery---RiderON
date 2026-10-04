// Web implementation of the `TokenStorage` interface expected by
// @rideron/api-client (see packages/api-client/src/tokenStorage.ts).
// Backed by `localStorage` — mirrors apps/customer's SecureStore-backed
// SecureTokenStorage, but for the browser.

const ACCESS_KEY = 'rideron.access_token';
const REFRESH_KEY = 'rideron.refresh_token';

export class LocalStorageTokenStorage {
  async getAccessToken() {
    return window.localStorage.getItem(ACCESS_KEY);
  }

  async getRefreshToken() {
    return window.localStorage.getItem(REFRESH_KEY);
  }

  async setTokens(tokens) {
    window.localStorage.setItem(ACCESS_KEY, tokens.access_token);
    window.localStorage.setItem(REFRESH_KEY, tokens.refresh_token);
  }

  async clearTokens() {
    window.localStorage.removeItem(ACCESS_KEY);
    window.localStorage.removeItem(REFRESH_KEY);
  }
}
