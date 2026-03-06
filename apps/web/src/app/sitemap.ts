import type { MetadataRoute } from 'next';

/**
 * Generate Sitemap
 *
 * Dynamically generates a sitemap for search engine crawlers.
 * Includes all static pages and dynamic routes like listings.
 *
 * In production, this should fetch actual listings from the API.
 */
export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const baseUrl = process.env.NEXT_PUBLIC_SITE_URL || 'https://djerbafun.com';

  // Static pages
  const staticPages: MetadataRoute.Sitemap = [
    {
      url: baseUrl,
      lastModified: new Date(),
      changeFrequency: 'daily',
      priority: 1,
    },
    {
      url: `${baseUrl}/en`,
      lastModified: new Date(),
      changeFrequency: 'daily',
      priority: 1,
    },
    {
      url: `${baseUrl}/ar`,
      lastModified: new Date(),
      changeFrequency: 'daily',
      priority: 1,
    },
    {
      url: `${baseUrl}/listings`,
      lastModified: new Date(),
      changeFrequency: 'daily',
      priority: 0.9,
    },
    {
      url: `${baseUrl}/en/listings`,
      lastModified: new Date(),
      changeFrequency: 'daily',
      priority: 0.9,
    },
    {
      url: `${baseUrl}/ar/listings`,
      lastModified: new Date(),
      changeFrequency: 'daily',
      priority: 0.9,
    },
    {
      url: `${baseUrl}/auth/login`,
      lastModified: new Date(),
      changeFrequency: 'monthly',
      priority: 0.5,
    },
    {
      url: `${baseUrl}/en/auth/login`,
      lastModified: new Date(),
      changeFrequency: 'monthly',
      priority: 0.5,
    },
    {
      url: `${baseUrl}/ar/auth/login`,
      lastModified: new Date(),
      changeFrequency: 'monthly',
      priority: 0.5,
    },
    {
      url: `${baseUrl}/auth/register`,
      lastModified: new Date(),
      changeFrequency: 'monthly',
      priority: 0.5,
    },
    {
      url: `${baseUrl}/en/auth/register`,
      lastModified: new Date(),
      changeFrequency: 'monthly',
      priority: 0.5,
    },
    {
      url: `${baseUrl}/ar/auth/register`,
      lastModified: new Date(),
      changeFrequency: 'monthly',
      priority: 0.5,
    },
  ];

  // Dynamic listings
  // In production, fetch from API and use location-first URL structure:
  // const listings = await fetchAllListings();
  // const listingPages = listings.flatMap(listing => {
  //   const locationSlug = listing.location.name
  //     .toLowerCase()
  //     .replace(/[^a-z0-9]+/g, '-')
  //     .replace(/(^-|-$)/g, '');
  //
  //   return [
  //     {
  //       url: `${baseUrl}/${locationSlug}/${listing.slug}`,
  //       lastModified: new Date(listing.updatedAt),
  //       changeFrequency: 'weekly' as const,
  //       priority: 0.8,
  //     },
  //     {
  //       url: `${baseUrl}/en/${locationSlug}/${listing.slug}`,
  //       lastModified: new Date(listing.updatedAt),
  //       changeFrequency: 'weekly' as const,
  //       priority: 0.8,
  //     },
  //     {
  //       url: `${baseUrl}/ar/${locationSlug}/${listing.slug}`,
  //       lastModified: new Date(listing.updatedAt),
  //       changeFrequency: 'weekly' as const,
  //       priority: 0.8,
  //     },
  //   ];
  // });

  // For now, return static pages only
  // In production, combine: [...staticPages, ...listingPages]
  return staticPages;
}
