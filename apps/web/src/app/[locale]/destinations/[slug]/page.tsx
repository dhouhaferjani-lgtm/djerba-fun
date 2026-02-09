import { Metadata } from 'next';
import { notFound } from 'next/navigation';
import { setRequestLocale } from 'next-intl/server';
import { MainLayout } from '@/components/templates/MainLayout';
import { DestinationContent } from '@/components/destinations/DestinationContent';
import { getCmsDestination } from '@/lib/api/server';
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

  const [cmsDestination, locationData] = await Promise.all([
    getCmsDestination(slug, locale),
    getLocationData(slug, locale),
  ]);

  if (!cmsDestination && !locationData) {
    notFound();
  }

  return (
    <MainLayout locale={locale}>
      <DestinationContent
        locale={locale}
        slug={slug}
        cmsDestination={cmsDestination}
        location={locationData?.location ?? null}
        listings={locationData?.listings ?? []}
      />
    </MainLayout>
  );
}
