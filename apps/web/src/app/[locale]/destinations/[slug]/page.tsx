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

// --- Slug Resolution ---
// CMS destination IDs may differ from our data keys (e.g. "houmet-souk" → "djerba")

const slugToDataKey: Record<string, string> = {
  djerba: 'djerba',
  dhaher: 'dhaher',
  desert: 'desert',
  'houmet-souk': 'djerba',
  guellala: 'dhaher',
  'lile-des-flamants-roses': 'desert',
};

function resolveDataKey(slug: string, cmsName?: string | null): string {
  if (slugToDataKey[slug]) return slugToDataKey[slug];
  const lowerName = (cmsName ?? '').toLowerCase();
  if (lowerName.includes('djerba')) return 'djerba';
  if (lowerName.includes('dhaher') || lowerName.includes('dahar')) return 'dhaher';
  if (lowerName.includes('desert') || lowerName.includes('désert') || lowerName.includes('sahara'))
    return 'desert';
  return slug;
}

// --- Destination-specific SEO metadata ---

const destinationSeoMeta: Record<
  string,
  { titleEn: string; titleFr: string; descEn: string; descFr: string }
> = {
  djerba: {
    titleEn: 'Djerba, Tunisia — UNESCO Island, Beaches & Culture | Go Adventure',
    titleFr: 'Djerba, Tunisie — Île UNESCO, Plages & Culture | Go Adventure',
    descEn:
      'Explore Djerba, the largest island in North Africa. UNESCO World Heritage Site with 20+ beaches, El Ghriba Synagogue, Djerbahood street art, and Houmt Souk markets. Book authentic tours and activities.',
    descFr:
      "Explorez Djerba, la plus grande île d'Afrique du Nord. Site du patrimoine mondial UNESCO avec plus de 20 plages, la synagogue de la Ghriba, le street art Djerbahood et les marchés de Houmt Souk. Réservez des tours et activités authentiques.",
  },
  dhaher: {
    titleEn: 'Dahar Region, Tunisia — Berber Villages, Trekking & Star Wars Sites | Go Adventure',
    titleFr:
      'Région du Dahar, Tunisie — Villages Berbères, Randonnée & Sites Star Wars | Go Adventure',
    descEn:
      'Discover the Dahar highlands of southern Tunisia. Trek the 194 km Great Dahar Crossing, visit Chenini & Douiret troglodyte villages, explore Star Wars filming locations at Ksar Ouled Soltane, and find dinosaur footprints.',
    descFr:
      'Découvrez les hauts plateaux du Dahar dans le sud tunisien. Parcourez les 194 km de la Grande Traversée du Dahar, visitez les villages troglodytiques de Chenini et Douiret, explorez les lieux de tournage Star Wars à Ksar Ouled Soltane.',
  },
  desert: {
    titleEn: 'Tunisian Sahara Desert — Douz, Ksar Ghilane & Chott el Jerid | Go Adventure',
    titleFr: 'Désert du Sahara Tunisien — Douz, Ksar Ghilane & Chott el Jerid | Go Adventure',
    descEn:
      'Explore the Tunisian Sahara: camel treks in Douz, hot springs at Ksar Ghilane oasis, the vast Chott el Jerid salt lake, and Star Wars filming locations near Tozeur. Book desert camps, 4x4 adventures, and more.',
    descFr:
      "Explorez le Sahara tunisien : méharées à Douz, sources chaudes à l'oasis de Ksar Ghilane, le vaste lac salé Chott el Jerid et les lieux de tournage Star Wars près de Tozeur. Réservez camps désertiques, aventures en 4x4 et plus.",
  },
};

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
  const image = cmsDestination?.image ?? locationData?.location.imageUrl;
  const isFr = locale === 'fr';
  const dataKey = resolveDataKey(slug, cmsDestination?.name);
  const seoMeta = destinationSeoMeta[dataKey];

  const title = seoMeta
    ? isFr
      ? seoMeta.titleFr
      : seoMeta.titleEn
    : `${name} — Tours & Activities | Go Adventure`;

  const description = seoMeta
    ? isFr
      ? seoMeta.descFr
      : seoMeta.descEn
    : isFr
      ? `Découvrez des tours et activités à ${name}. Réservez des expériences authentiques avec des guides locaux.`
      : `Discover amazing tours and activities in ${name}. Book authentic experiences with local guides.`;

  return {
    title,
    description,
    openGraph: {
      title: seoMeta ? title : `Explore ${name} | Go Adventure`,
      description,
      images: image ? [image] : [],
      type: 'website',
      locale: isFr ? 'fr_FR' : 'en_US',
    },
    alternates: {
      languages: {
        en: `/en/destinations/${slug}`,
        fr: `/fr/destinations/${slug}`,
      },
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
