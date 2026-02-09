import { Metadata } from 'next';
import { notFound, redirect } from 'next/navigation';
import { headers, cookies } from 'next/headers';
import { EventJsonLd, BreadcrumbJsonLd } from '@/components/seo/JsonLd';
import { resolveTranslation } from '@/lib/utils/translate';
import ListingDetailClient from '../../listings/[slug]/listing-detail-client';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';
const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'http://localhost:3000';

// Reserved routes that should NOT be treated as locations
const RESERVED_ROUTES = [
  'dashboard',
  'cart',
  'checkout',
  'listings',
  'vendors',
  'auth',
  'login',
  'register',
  'tours',
  'events',
  'en',
  'ar',
];

// Fetch listing with user's currency preference forwarded
// Using cache: 'no-store' because currency is user-specific
async function fetchListing(slug: string, locale: string = 'en') {
  try {
    // Get user's IP from incoming request headers (fallback for first-time visitors)
    const headersList = await headers();
    const forwardedFor = headersList.get('x-forwarded-for');
    const realIp = headersList.get('x-real-ip');
    const cfConnectingIp = headersList.get('cf-connecting-ip'); // Cloudflare
    const userIp = forwardedFor?.split(',')[0]?.trim() || realIp || cfConnectingIp || '';

    // Get user's detected currency from cookie (set by client-side list page)
    // This ensures consistent currency between client-side (list) and server-side (detail) pages
    const cookieStore = await cookies();
    const userCurrency = cookieStore.get('user_currency')?.value || '';

    const response = await fetch(`${API_URL}/listings/${slug}`, {
      cache: 'no-store', // Disable cache - currency is user-specific
      headers: {
        'Accept-Language': locale,
        'X-Forwarded-For': userIp,
        ...(userCurrency && { 'X-User-Currency': userCurrency }),
      },
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
}

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string; locale: string; location: string }>;
}): Promise<Metadata> {
  const { slug, locale, location } = await params;

  // Check if this is a reserved route
  if (RESERVED_ROUTES.includes(location)) {
    return {
      title: 'Not Found',
    };
  }

  const listing = await fetchListing(slug, locale);

  if (!listing) {
    return {
      title: 'Listing Not Found',
      description: 'The requested listing could not be found.',
    };
  }

  const title = resolveTranslation(listing.title, locale);
  const description = resolveTranslation(listing.description, locale);
  const firstImage = listing.media?.[0]?.url;
  const metaDescription =
    description.length > 160 ? `${description.substring(0, 157)}...` : description;

  return {
    title: title,
    description: metaDescription,
    openGraph: {
      title: title,
      description: metaDescription,
      type: 'website',
      url: `${SITE_URL}/${locale === 'fr' ? '' : locale + '/'}${location}/${slug}`,
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
      locale: locale === 'fr' ? 'fr_FR' : locale === 'en' ? 'en_US' : 'ar_AR',
    },
    twitter: {
      card: 'summary_large_image',
      title: title,
      description: metaDescription,
      images: firstImage ? [firstImage] : [],
    },
    alternates: {
      canonical: `${SITE_URL}/${locale === 'fr' ? '' : locale + '/'}${location}/${slug}`,
      languages: {
        fr: `${SITE_URL}/${location}/${slug}`,
        en: `${SITE_URL}/en/${location}/${slug}`,
        ar: `${SITE_URL}/ar/${location}/${slug}`,
      },
    },
  };
}

export default async function ListingDetailPage({
  params,
}: {
  params: Promise<{ slug: string; locale: string; location: string }>;
}) {
  const { slug, locale, location } = await params;

  // Redirect reserved routes to 404
  if (RESERVED_ROUTES.includes(location)) {
    notFound();
  }

  const listing = await fetchListing(slug, locale);

  if (!listing) {
    notFound();
  }

  // Verify the location matches the listing's location
  // If not, redirect to correct URL
  const listingLocationSlug = listing.location?.name
    ?.toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '');

  if (listingLocationSlug && listingLocationSlug !== location) {
    const correctUrl = `/${locale === 'fr' ? '' : locale + '/'}${listingLocationSlug}/${slug}`;
    redirect(correctUrl as any);
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
  const currency = listing.pricing?.displayCurrency || 'TND';

  // Get location information
  const locationName =
    listing.meetingPoint?.address?.split(',')[0]?.trim() || listing.location?.name || 'Tunisia';
  const locationAddress = listing.meetingPoint?.address || '';

  // Get vendor information
  const vendorName = listing.vendor?.companyName || listing.vendor?.company_name || 'Go Adventure';
  const vendorSlug = listing.vendor?.slug || listing.vendor?.id;
  const vendorImage = listing.vendor?.avatarUrl;

  // Determine proper startDate/endDate based on service type
  const isEvent = listing.serviceType === 'event';
  const startDate = isEvent ? listing.startDate : undefined;
  const endDate = isEvent ? listing.endDate : undefined;
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
          url={`${SITE_URL}/${locale === 'fr' ? '' : locale + '/'}${location}/${slug}`}
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
            url: `${SITE_URL}/${locale === 'fr' ? '' : locale + '/'}${location}/${slug}`,
            seller: {
              '@type': 'Organization',
              name: vendorName,
            },
          }}
          organizer={{
            '@type': 'Organization',
            name: vendorName,
            url: vendorSlug
              ? `${SITE_URL}/${locale === 'fr' ? '' : locale + '/'}vendors/${vendorSlug}`
              : undefined,
            image: vendorImage,
          }}
        />
      )}

      {/* Breadcrumb Schema */}
      <BreadcrumbJsonLd
        itemListElement={[
          {
            '@type': 'ListItem',
            position: 1,
            name: 'Home',
            item: `${SITE_URL}/${locale === 'fr' ? '' : locale}`,
          },
          {
            '@type': 'ListItem',
            position: 2,
            name: locationName,
            item: `${SITE_URL}/${locale === 'fr' ? '' : locale + '/'}${location}`,
          },
          {
            '@type': 'ListItem',
            position: 3,
            name: title,
            item: `${SITE_URL}/${locale === 'fr' ? '' : locale + '/'}${location}/${slug}`,
          },
        ]}
      />

      {/* Client Component */}
      <ListingDetailClient listing={listing} locale={locale} slug={slug} />
    </>
  );
}
