import { Metadata } from 'next';
import { notFound } from 'next/navigation';
import { setRequestLocale } from 'next-intl/server';
import { getTranslations } from 'next-intl/server';
import { MainLayout } from '@/components/templates/MainLayout';
import { ListingCard } from '@/components/molecules/ListingCard';
import { DestinationMapSection } from '@/components/maps/DestinationMapSection';
import { getCmsDestination } from '@/lib/api/server';
import type { Locale } from '@/i18n/routing';
import type { ListingSummary } from '@go-adventure/schemas';

interface Location {
  id: string;
  name: string;
  slug: string;
  description: string | null;
  latitude: number | null;
  longitude: number | null;
  imageUrl: string | null;
  listingsCount: number;
  city: string | null;
  region: string | null;
  country: string;
}

interface DestinationPageProps {
  params: Promise<{
    locale: string;
    slug: string;
  }>;
}

async function getLocationData(
  slug: string,
  locale: string
): Promise<{
  location: Location;
  listings: ListingSummary[];
} | null> {
  try {
    const response = await fetch(
      `${process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1'}/locations/${slug}?locale=${locale}`,
      {
        next: { revalidate: 3600 },
      }
    );

    if (!response.ok) {
      return null;
    }

    return response.json();
  } catch (error) {
    console.error('Error fetching location:', error);
    return null;
  }
}

export async function generateMetadata({ params }: DestinationPageProps): Promise<Metadata> {
  const { locale, slug } = await params;
  const [cmsDestination, locationData] = await Promise.all([
    getCmsDestination(slug, locale),
    getLocationData(slug, locale),
  ]);

  if (!cmsDestination && !locationData) {
    return { title: 'Destination Not Found' };
  }

  const name = cmsDestination?.name ?? locationData?.location.name ?? slug;
  const description =
    locale === 'fr'
      ? (cmsDestination?.description_fr ?? locationData?.location.description)
      : (cmsDestination?.description_en ?? locationData?.location.description);
  const image = cmsDestination?.image ?? locationData?.location.imageUrl;

  return {
    title: `${name} - Tours & Activities`,
    description:
      description ||
      `Discover amazing tours and activities in ${name}. Book authentic experiences with local guides.`,
    openGraph: {
      title: `Explore ${name}`,
      description: description || `Discover experiences in ${name}`,
      images: image ? [image] : [],
    },
  };
}

export default async function DestinationPage({ params }: DestinationPageProps) {
  const { locale, slug } = await params;
  setRequestLocale(locale);

  const t = await getTranslations('destinations');
  const [cmsDestination, locationData] = await Promise.all([
    getCmsDestination(slug, locale),
    getLocationData(slug, locale),
  ]);

  if (!cmsDestination && !locationData) {
    notFound();
  }

  // Merge data — CMS takes precedence
  const location = locationData?.location ?? null;
  const listings = locationData?.listings ?? [];

  const displayName = cmsDestination?.name ?? location?.name ?? slug;
  const displayDescription =
    locale === 'fr'
      ? (cmsDestination?.description_fr ?? location?.description)
      : (cmsDestination?.description_en ?? location?.description);
  const displayImage = cmsDestination?.image ?? location?.imageUrl;
  const locationMeta = location
    ? [location.city, location.region, location.country].filter(Boolean).join(', ')
    : null;
  const listingsCount = location?.listingsCount ?? listings.length;
  const center: [number, number] | undefined =
    location?.latitude && location?.longitude ? [location.latitude, location.longitude] : undefined;

  return (
    <MainLayout locale={locale}>
      {/* Hero Section */}
      <section className="relative h-[400px] overflow-hidden">
        {displayImage ? (
          <div
            className="absolute inset-0 bg-cover bg-center"
            style={{ backgroundImage: `url(${displayImage})` }}
          />
        ) : (
          <div className="absolute inset-0 bg-gradient-to-br from-primary to-primary-light" />
        )}
        <div className="absolute inset-0 bg-black/40" />
        <div className="relative container mx-auto px-4 h-full flex items-center">
          <div className="text-white max-w-3xl">
            <h1 className="text-4xl md:text-5xl font-bold mb-4">{displayName}</h1>
            {displayDescription && (
              <p className="text-lg md:text-xl mb-6 text-white/90">{displayDescription}</p>
            )}
            <div className="flex items-center gap-4 text-white/80">
              {locationMeta && (
                <span className="flex items-center gap-2">
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                    />
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                  </svg>
                  {locationMeta}
                </span>
              )}
              {listingsCount > 0 && (
                <span className="flex items-center gap-2">
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                    />
                  </svg>
                  {listingsCount} {listingsCount === 1 ? 'experience' : 'experiences'}
                </span>
              )}
            </div>
          </div>
        </div>
      </section>

      {/* Map Section */}
      {listings.length > 0 && center && (
        <DestinationMapSection listings={listings} locale={locale as Locale} center={center} />
      )}

      {/* Listings Section */}
      <section className="py-12">
        <div className="container mx-auto px-4">
          {listings.length > 0 ? (
            <>
              <div className="flex justify-between items-center mb-8">
                <h2 className="text-3xl font-bold">
                  {t('available_experiences')} ({listingsCount})
                </h2>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                {listings.map((listing) => (
                  <ListingCard key={listing.id} listing={listing} locale={locale} />
                ))}
              </div>
            </>
          ) : (
            <div className="text-center py-16 bg-neutral-light rounded-lg">
              <svg
                className="w-16 h-16 mx-auto text-neutral-400 mb-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                />
              </svg>
              <h3 className="text-xl font-semibold text-neutral-900 mb-2">
                {t('experiences_coming_soon')}
              </h3>
              <p className="text-neutral-600">
                {t('experiences_coming_soon_desc', { name: displayName })}
              </p>
            </div>
          )}
        </div>
      </section>
    </MainLayout>
  );
}
