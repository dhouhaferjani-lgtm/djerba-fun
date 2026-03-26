'use client';

import { createContext, useContext, type ReactNode } from 'react';

/**
 * Branding URLs provided by server-side rendering.
 * Used to prevent logo flash by providing logo URLs before client-side API call.
 */
interface BrandingUrls {
  logoLight: string | null;
  logoDark: string | null;
  platformName: string;
}

const BrandingContext = createContext<BrandingUrls | null>(null);

interface BrandingProviderProps {
  children: ReactNode;
  branding: BrandingUrls;
}

/**
 * Provider that injects server-fetched branding URLs into the React tree.
 * This prevents the logo flash issue by making logo URLs available
 * immediately without waiting for client-side API calls.
 */
export function BrandingProvider({ children, branding }: BrandingProviderProps) {
  return <BrandingContext.Provider value={branding}>{children}</BrandingContext.Provider>;
}

/**
 * Hook to access server-provided branding URLs.
 * Returns null if BrandingProvider is not in the tree (fallback to client fetch).
 */
export function useBranding(): BrandingUrls | null {
  return useContext(BrandingContext);
}
