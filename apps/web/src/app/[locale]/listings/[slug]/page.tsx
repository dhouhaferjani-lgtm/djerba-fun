import { Metadata } from 'next';
import { notFound } from 'next/navigation';
import { cache } from 'react';
import { EventJsonLd, BreadcrumbJsonLd } from '@/components/seo/JsonLd';
import { resolveTranslation } from '@/lib/utils/translate';
import ListingDetailClient from './listing-detail-client';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';
const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'http://localhost:3000';

// Use React cache() to deduplicate fetch requests between generateMetadata() and page component
const fetchListing = cache(async (slug: string) => {
  try {
    const response = await fetch(`${API_URL}/listings/${slug}`, {
      next: { revalidate: 3600 }, // Revalidate every hour
    });

    if (!response.ok) {
      return null;
    }

    const result = await response.json();
    return result.data;
  } catch (error) {
    console.error('Error fetching listing:', error);
    return null;
  }
});

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string; locale: string }>;
}): Promise<Metadata> {
  const { slug, locale } = await params;
  const listing = await fetchListing(slug);

  if (!listing) {
    return {
      title: 'Listing Not Found',
      description: 'The requested listing could not be found.',
    };
  }

  const title = resolveTranslation(listing.title, locale);
  const description = resolveTranslation(listing.description, locale);
  const firstImage = listing.media?.[0]?.url;

  // Truncate description to 160 characters for meta
  const metaDescription =
    description.length > 160 ? `${description.substring(0, 157)}...` : description;

  return {
    title: `${title} | Go Adventure`,
    description: metaDescription,
    openGraph: {
      title: title,
      description: metaDescription,
      type: 'website',
      url: `${SITE_URL}/${locale}/listings/${slug}`,
      images: firstImage
        ? [
            {
              url: firstImage,
              width: 1200,
              height: 630,
              alt: title,
            },
          ]
        : [],
      siteName: 'Go Adventure',
      locale: locale === 'fr' ? 'fr_FR' : 'en_US',
    },
    twitter: {
      card: 'summary_large_image',
      title: title,
      description: metaDescription,
      images: firstImage ? [firstImage] : [],
    },
    alternates: {
      canonical: `${SITE_URL}/${locale}/listings/${slug}`,
      languages: {
        en: `${SITE_URL}/en/listings/${slug}`,
        fr: `${SITE_URL}/fr/listings/${slug}`,
      },
    },
  };
}

export default async function ListingDetailPage({
  params,
}: {
  params: Promise<{ slug: string; locale: string }>;
}) {
  const { slug, locale } = await params;
  const listing = await fetchListing(slug);

  if (!listing) {
    notFound();
  }

  const title = resolveTranslation(listing.title, locale);
  const description = resolveTranslation(listing.description, locale);

  // Include up to 5 images for richer search results
  const images =
    listing.media && listing.media.length > 0
      ? listing.media.slice(0, 5).map((m: any) => m.url)
      : undefined;

  // Get price from pricing with proper currency handling
  const priceTnd = listing.pricing?.tndPrice || 0;
  const priceEur = listing.pricing?.eurPrice || 0;
  const displayPrice = listing.pricing?.displayPrice || priceEur || priceTnd || 0;
  const numericPrice = typeof displayPrice === 'string' ? parseFloat(displayPrice) : displayPrice;
  const currency = listing.pricing?.currency || listing.pricing?.displayCurrency || 'TND';

  // Get location information - use meetingPoint as primary source
  const locationName = listing.meetingPoint?.address?.split(',')[0]?.trim() || 'Tunisia';
  const locationAddress = listing.meetingPoint?.address || '';

  // Get vendor information
  const vendorName = listing.vendor?.companyName || listing.vendor?.company_name || 'Go Adventure';
  const vendorSlug = listing.vendor?.slug || listing.vendor?.id;
  const vendorImage = listing.vendor?.avatarUrl;

  // Determine proper startDate/endDate based on service type
  // Events have explicit dates, tours should not have fallback dates
  const isEvent = listing.serviceType === 'event';
  const startDate = isEvent ? listing.startDate : undefined;
  const endDate = isEvent ? listing.endDate : undefined;

  // Only render Event schema if we have a valid startDate
  // For tours without dates, we'll skip the Event schema
  const shouldRenderEventSchema = isEvent && startDate;

  return (
    <>
      {/* Event Schema - only for events with valid dates */}
      {shouldRenderEventSchema && (
        <EventJsonLd
          name={title}
          description={description}
          image={images}
          startDate={startDate!}
          endDate={endDate}
          url={`${SITE_URL}/${locale}/listings/${slug}`}
          eventStatus="https://schema.org/EventScheduled"
          eventAttendanceMode="https://schema.org/OfflineEventAttendanceMode"
          location={{
            '@type': 'Place',
            name: locationName,
            address: locationAddress,
          }}
          offers={{
            '@type': 'Offer',
            price: numericPrice.toString(),
            priceCurrency: currency,
            availability:
              listing.status === 'published'
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
            url: `${SITE_URL}/${locale}/listings/${slug}`,
            seller: {
              '@type': 'Organization',
              name: vendorName,
            },
          }}
          organizer={{
            '@type': 'Organization',
            name: vendorName,
            url: vendorSlug ? `${SITE_URL}/${locale}/vendors/${vendorSlug}` : undefined,
            image: vendorImage,
          }}
        />
      )}

      {/* For tours (recurring activities), we could add Product schema in the future */}
      {/* This would use AggregateOffer to represent multiple availability slots */}

      {/* Breadcrumb Schema */}
      <BreadcrumbJsonLd
        itemListElement={[
          {
            '@type': 'ListItem',
            position: 1,
            name: 'Home',
            item: `${SITE_URL}/${locale}`,
          },
          {
            '@type': 'ListItem',
            position: 2,
            name: 'Listings',
            item: `${SITE_URL}/${locale}/listings`,
          },
          {
            '@type': 'ListItem',
            position: 3,
            name: title,
            item: `${SITE_URL}/${locale}/listings/${slug}`,
          },
        ]}
      />

      {/* Client Component */}
      <ListingDetailClient listing={listing} locale={locale} slug={slug} />
    </>
  );
}
