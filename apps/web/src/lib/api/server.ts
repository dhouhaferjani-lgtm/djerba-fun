/**
 * Server-side API utilities for Next.js Server Components and generateMetadata.
 * These functions can be called from server contexts where localStorage is not available.
 */

import type { PlatformSettingsResponse, ListingSummary } from '@go-adventure/schemas';

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
    brandPillar1: settings?.data?.branding?.brandPillar1 ?? null,
    brandPillar2: settings?.data?.branding?.brandPillar2 ?? null,
    brandPillar3: settings?.data?.branding?.brandPillar3 ?? null,
    platformName: settings?.data?.platform?.name ?? 'Go Adventure',
    tagline: settings?.data?.platform?.tagline ?? null,
    description: settings?.data?.seo?.metaDescription ?? null,
    // Analytics IDs from CMS
    ga4MeasurementId: settings?.data?.analytics?.ga4MeasurementId ?? null,
    gtmContainerId: settings?.data?.analytics?.gtmContainerId ?? null,
  };
}

/**
 * Fetch Schema.org JSON-LD data from the API (server-side).
 * Returns pre-formatted structured data for search engines.
 */
export async function getSchemaOrgData(locale?: string): Promise<Record<string, unknown> | null> {
  try {
    const params = new URLSearchParams();
    if (locale) params.append('locale', locale);
    const queryString = params.toString();

    const response = await fetch(
      `${API_URL}/platform/schema${queryString ? `?${queryString}` : ''}`,
      {
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        cache: 'no-store',
      }
    );

    if (!response.ok) {
      console.error(`Failed to fetch schema.org data: ${response.status}`);
      return null;
    }

    return response.json();
  } catch (error) {
    console.error('Error fetching schema.org data:', error);
    return null;
  }
}

/**
 * Get Event of the Year data from platform settings.
 * Returns default values if settings cannot be fetched.
 */
export async function getEventOfYearData(locale?: string) {
  const settings = await getPlatformSettings(locale);
  const eventOfYear = (settings?.data as any)?.eventOfYear;

  return {
    enabled: eventOfYear?.enabled ?? true,
    tag: eventOfYear?.tag ?? 'Event of the Year',
    title: eventOfYear?.title ?? null,
    description: eventOfYear?.description ?? null,
    link: eventOfYear?.link ?? null,
    image: eventOfYear?.image ?? null,
  };
}

/**
 * Get Hero Section text from platform settings.
 * Returns null values if settings cannot be fetched (frontend will use translations as fallback).
 * The title is a single sentence where the first word is styled in green, rest in white.
 */
export async function getHeroData(locale?: string) {
  const settings = await getPlatformSettings(locale);
  const hero = (settings?.data as any)?.hero;

  return {
    title: hero?.title ?? null,
    subtitle: hero?.subtitle ?? null,
  };
}

/**
 * Get Brand Pillars text from platform settings.
 * Returns null values if settings cannot be fetched (frontend will use translations as fallback).
 */
export async function getBrandPillarsData(locale?: string) {
  const settings = await getPlatformSettings(locale);
  const brandPillars = (settings?.data as any)?.brandPillars;

  return {
    pillar1: {
      title: brandPillars?.pillar1?.title ?? null,
      description: brandPillars?.pillar1?.description ?? null,
    },
    pillar2: {
      title: brandPillars?.pillar2?.title ?? null,
      description: brandPillars?.pillar2?.description ?? null,
    },
    pillar3: {
      title: brandPillars?.pillar3?.title ?? null,
      description: brandPillars?.pillar3?.description ?? null,
    },
  };
}

/**
 * Get contact information from platform settings.
 * Returns default values if settings cannot be fetched.
 */
export async function getContactInfo(locale?: string) {
  const settings = await getPlatformSettings(locale);

  return {
    supportEmail: settings?.data?.contact?.supportEmail ?? 'contact@go-adventure.net',
    generalEmail: settings?.data?.contact?.generalEmail ?? null,
    phone: settings?.data?.contact?.phone ?? null,
    whatsapp: settings?.data?.contact?.whatsapp ?? null,
  };
}

/**
 * Get Featured Destinations from platform settings.
 * Returns empty array if settings cannot be fetched (frontend will use hardcoded defaults).
 */
export async function getFeaturedDestinations(locale?: string) {
  const settings = await getPlatformSettings(locale);
  const destinations = (settings?.data as Record<string, unknown>)?.featuredDestinations;
  return (
    (destinations as Array<{
      id: string;
      name: string;
      description_en: string;
      description_fr: string;
      image: string;
    }>) ?? []
  );
}

/**
 * Fetch featured listings from the API (server-side).
 * Returns listings marked as featured by admin for the home page.
 */
export async function getFeaturedListings(limit: number = 3): Promise<ListingSummary[]> {
  try {
    const response = await fetch(`${API_URL}/listings/featured?limit=${limit}`, {
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      next: { revalidate: 300 }, // Cache for 5 minutes
    });

    if (!response.ok) {
      console.error(`Failed to fetch featured listings: ${response.status}`);
      return [];
    }

    const data = await response.json();
    return data.data || [];
  } catch (error) {
    console.error('Error fetching featured listings:', error);
    return [];
  }
}
