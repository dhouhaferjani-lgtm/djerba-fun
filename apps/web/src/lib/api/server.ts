/**
 * Server-side API utilities for Next.js Server Components and generateMetadata.
 * These functions can be called from server contexts where localStorage is not available.
 */

import type { PlatformSettingsResponse } from '@go-adventure/schemas';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';

/**
 * Fetch platform settings from the API (server-side).
 * Includes caching for better performance.
 */
export async function getPlatformSettings(
  locale?: string
): Promise<PlatformSettingsResponse | null> {
  try {
    const params = new URLSearchParams();
    if (locale) params.append('locale', locale);
    const queryString = params.toString();

    const response = await fetch(
      `${API_URL}/platform/settings${queryString ? `?${queryString}` : ''}`,
      {
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        // Disable caching to ensure fresh branding data
        cache: 'no-store',
      }
    );

    if (!response.ok) {
      console.error(`Failed to fetch platform settings: ${response.status}`);
      return null;
    }

    return response.json();
  } catch (error) {
    console.error('Error fetching platform settings:', error);
    return null;
  }
}

/**
 * Get branding URLs from platform settings.
 * Returns null values if settings cannot be fetched.
 */
export async function getBrandingUrls(locale?: string) {
  const settings = await getPlatformSettings(locale);

  return {
    logoLight: settings?.data?.branding?.logoLight ?? null,
    logoDark: settings?.data?.branding?.logoDark ?? null,
    favicon: settings?.data?.branding?.favicon ?? null,
    ogImage: settings?.data?.branding?.ogImage ?? null,
    appleTouchIcon: settings?.data?.branding?.appleTouchIcon ?? null,
    heroBanner: settings?.data?.branding?.heroBanner ?? null,
    platformName: settings?.data?.platform?.name ?? 'Go Adventure',
    tagline: settings?.data?.platform?.tagline ?? null,
    description: settings?.data?.seo?.metaDescription ?? null,
  };
}
