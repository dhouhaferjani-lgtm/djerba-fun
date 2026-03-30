'use client';

import { createContext, useContext, useEffect, type ReactNode } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { useCurrentUser, useLogin, useLogout, useRegister } from '../api/hooks';
import { queryKeys } from '../api/query-keys';
import type { User } from '@djerba-fun/schemas';

interface RegisterData {
  email: string;
  password: string;
  passwordConfirmation: string;
  firstName: string;
  lastName: string;
  displayName: string;
  role: string;
  cfTurnstileResponse?: string;
}

interface AuthContextValue {
  user: User | null | undefined;
  isLoading: boolean;
  isAuthenticated: boolean;
  login: (email: string, password: string, cfTurnstileResponse?: string) => Promise<void>;
  register: (data: RegisterData) => Promise<void>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
  const queryClient = useQueryClient();
  const { data: userData, isLoading } = useCurrentUser();
  const loginMutation = useLogin();
  const registerMutation = useRegister();
  const logoutMutation = useLogout();

  // Handle 401 unauthorized errors from API
  useEffect(() => {
    const handleUnauthorized = () => {
      // Reset query data to null to clear any error state
      // This allows the user to continue as a guest
      queryClient.setQueryData(queryKeys.user.me, null);
      // Invalidate to notify observers of the change
      queryClient.invalidateQueries({ queryKey: queryKeys.user.me });
    };

    window.addEventListener('auth:unauthorized', handleUnauthorized);
    return () => window.removeEventListener('auth:unauthorized', handleUnauthorized);
  }, [queryClient]);

  const login = async (email: string, password: string, cfTurnstileResponse?: string) => {
    await loginMutation.mutateAsync({ email, password, cfTurnstileResponse });
  };

  const register = async (data: RegisterData) => {
    await registerMutation.mutateAsync(data);
  };

  const logout = async () => {
    await logoutMutation.mutateAsync();
  };

  const value: AuthContextValue = {
    user: userData ?? null,
    isLoading,
    isAuthenticated: !!userData,
    login,
    register,
    logout,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
