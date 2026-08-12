import React, { createContext, useContext, useEffect, useMemo, useState, useCallback } from 'react';
import { apiClient, setSessionExpiredHandler } from '../services/httpClient';

export type AuthStatus = 'loading' | 'authenticated' | 'unauthenticated';

interface AuthContextValue {
  status: AuthStatus;
  /** Call after a successful auth.partnerLogin() (which already persisted tokens) to flip the root navigator over to AppTabs. */
  signIn: () => void;
  /** Calls the backend logout endpoint best-effort, then always clears local state. */
  signOut: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | null>(null);

/**
 * Mirrors apps/customer/src/hooks/useAuth.tsx. Determines AuthStack vs
 * AppTabs by checking for a persisted token pair at boot (SecureStore-backed,
 * see services/tokenStorage.ts) — a local presence check only, not a network
 * call. An expired/invalid refresh token is discovered lazily on the first
 * authenticated API call and routes back to AuthStack via onSessionExpired.
 *
 * Unlike the customer app there's no OTP/new-user branch here: partner
 * login (POST /auth/partner/login) is a single phone+password call that
 * either succeeds with tokens or fails outright — no separate profile-setup
 * step, since delivery partner accounts are provisioned by ops, not
 * self-registered.
 */
export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [status, setStatus] = useState<AuthStatus>('loading');

  useEffect(() => {
    let cancelled = false;
    apiClient.auth.hasStoredSession().then((hasSession) => {
      if (!cancelled) {
        setStatus(hasSession ? 'authenticated' : 'unauthenticated');
      }
    });
    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    setSessionExpiredHandler(() => setStatus('unauthenticated'));
    return () => setSessionExpiredHandler(() => {});
  }, []);

  const signIn = useCallback(() => setStatus('authenticated'), []);

  const signOut = useCallback(async () => {
    try {
      await apiClient.auth.logout();
    } catch {
      // Best-effort — still clear local session below even if the network call fails
      // (e.g. token already expired server-side, or the device is offline).
      await apiClient.http.getTokenStorage().clearTokens();
    }
    setStatus('unauthenticated');
  }, []);

  const value = useMemo(() => ({ status, signIn, signOut }), [status, signIn, signOut]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error('useAuth() must be called within an <AuthProvider>.');
  }
  return ctx;
}
