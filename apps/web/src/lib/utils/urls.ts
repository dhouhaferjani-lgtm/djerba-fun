/**
 * URL generation utilities for the Go Adventure marketplace
 *
 * Handles the location-first URL structure:
 * - French (default): /{location}/{slug}
 * - English: /en/{location}/{slug}
 * - Arabic: /ar/{location}/{slug}
 */

import type { Locale } from '@/i18n/routing';

/**
 * Generate a listing detail URL with location-first structure
 */
export function getListingUrl(
  slug: string,
  location: string | { name: string },
  locale: Locale = 'fr'
): string {
  // Normalize location to a slug-friendly format
  const locationSlug =
    typeof location === 'string'
      ? location
      : location.name
          .toLowerCase()
          .replace(/[^a-z0-9]+/g, '-')
          .replace(/(^-|-$)/g, '');

  // French is default (no locale prefix)
  if (locale === 'fr') {
    return `/${locationSlug}/${slug}`;
  }

  // Other locales get prefix
  return `/${locale}/${locationSlug}/${slug}`;
}

/**
 * Generate a listings index/browse URL
 */
export function getListingsUrl(locale: Locale = 'fr', filters?: Record<string, string>): string {
  const base = locale === 'fr' ? '/listings' : `/${locale}/listings`;

  if (!filters || Object.keys(filters).length === 0) {
    return base;
  }

  const query = new URLSearchParams(filters).toString();
  return `${base}?${query}`;
}

/**
 * Generate a listings index URL (alias for backwards compatibility)
 */
export function getListingsIndexUrl(locale: Locale = 'fr'): string {
  return getListingsUrl(locale);
}

/**
 * Generate a vendor profile URL
 */
export function getVendorUrl(slugOrId: string, locale: Locale = 'fr'): string {
  const base = locale === 'fr' ? '/vendors' : `/${locale}/vendors`;
  return `${base}/${slugOrId}`;
}

/**
 * Generate a location page URL
 */
export function getLocationUrl(location: string, locale: Locale = 'fr'): string {
  const locationSlug = location
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '');

  if (locale === 'fr') {
    return `/${locationSlug}`;
  }

  return `/${locale}/${locationSlug}`;
}
