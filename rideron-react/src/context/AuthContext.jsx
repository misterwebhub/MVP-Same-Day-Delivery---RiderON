import { createContext, useCallback, useContext, useEffect, useState } from 'react';
import { apiClient, setSessionExpiredHandler } from '../lib/apiClient';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  // 'loading' | 'guest' | 'authed'
  const [status, setStatus] = useState('loading');
  const [profile, setProfile] = useState(null);

  const loadProfile = useCallback(async () => {
    try {
      const me = await apiClient.profile.get();
      setProfile(me);
      setStatus('authed');
      return me;
    } catch {
      setProfile(null);
      setStatus('guest');
      return null;
    }
  }, []);

  useEffect(() => {
    setSessionExpiredHandler(() => {
      setProfile(null);
      setStatus('guest');
    });

    (async () => {
      const hasSession = await apiClient.auth.hasStoredSession();
      if (hasSession) {
        await loadProfile();
      } else {
        setStatus('guest');
      }
    })();
  }, [loadProfile]);

  const requestOtp = useCallback((phone) => apiClient.auth.requestOtp({ phone, purpose: 'login' }), []);

  const verifyOtp = useCallback(async (phone, otp) => {
    const result = await apiClient.auth.verifyOtp({ phone, otp });
    if (!result.is_new_user) {
      await loadProfile();
    } else {
      setStatus('guest'); // still needs completeProfile
    }
    return result;
  }, [loadProfile]);

  const completeProfile = useCallback(async (name, email) => {
    await apiClient.auth.completeProfile({ name, email: email || null });
    await loadProfile();
  }, [loadProfile]);

  const logout = useCallback(async () => {
    try {
      await apiClient.auth.logout();
    } catch {
      // Ignore network errors — clear local state regardless.
    }
    setProfile(null);
    setStatus('guest');
  }, []);

  const value = {
    status,
    profile,
    isAuthenticated: status === 'authed',
    requestOtp,
    verifyOtp,
    completeProfile,
    logout,
    refreshProfile: loadProfile,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
