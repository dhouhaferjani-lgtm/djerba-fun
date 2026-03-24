'use client';

import { useTranslations, useLocale } from 'next-intl';
import Image from 'next/image';
import { Link } from '@/i18n/navigation';

// Type for dynamic CMS categories
interface ExperienceCategory {
  id: string;
  name: string;
  description: string | null;
  image: string | null;
  link: string;
  displayOrder: number;
}

interface CmsData {
  enabled: boolean;
  title: string | null;
  subtitle: string | null;
  categories?: ExperienceCategory[];
}

// Fallback service types with their translation keys (used when no CMS categories configured)
const fallbackServiceTypes = [
  { id: 'tour', slug: 'tour', labelKey: 'tours' },
  { id: 'nautical', slug: 'nautical', labelKey: 'nautical' },
  { id: 'accommodation', slug: 'accommodation', labelKey: 'accommodations' },
  { id: 'event', slug: 'event', labelKey: 'events' },
] as const;

// Default animated GIF icons by service type slug (used as fallback)
const defaultImages: Record<string, string> = {
  tour: '/images/experiences/activites.gif',
  nautical: '/images/experiences/nautique.gif',
  accommodation: '/images/experiences/hebergements.gif',
  event: '/images/experiences/evenements.gif',
};

const defaultFallbackImage = '/images/experiences/activites.gif';

interface CategoryCardProps {
  category: {
    id: string;
    name: string;
    image: string | null;
    link: string;
  };
  locale: string;
}

function CategoryCard({ category, locale }: CategoryCardProps) {
  // Use CMS image if available, otherwise fall back to default GIF for known types
  const image = category.image || defaultImages[category.id] || defaultFallbackImage;

  // Determine if image is a GIF (should not be optimized)
  const isGif = image.toLowerCase().endsWith('.gif');

  // Build the link - handle both relative and absolute URLs
  const href = category.link.startsWith('http')
    ? category.link
    : category.link.startsWith('/')
      ? `/${locale}${category.link}`
      : `/${locale}/${category.link}`;

  return (
    <Link
      href={href as any}
      className="group relative block overflow-hidden rounded-2xl h-56 md:h-64"
    >
      {/* Category Image */}
      <Image
        src={image}
        alt={category.name}
        fill
        sizes="(max-width: 640px) 100vw, (max-width: 768px) 50vw, 25vw"
        className="object-cover transition-transform duration-500 group-hover:scale-110"
        unoptimized={isGif}
      />

      {/* Gradient Overlay */}
      <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent" />

      {/* Title */}
      <div className="absolute inset-0 flex items-center justify-center p-6">
        <h3 className="text-center font-display text-xl md:text-2xl font-bold text-white uppercase tracking-wide drop-shadow-lg">
          {category.name}
        </h3>
      </div>

      {/* Hover Effect Border */}
      <div className="absolute inset-0 border-4 border-transparent group-hover:border-white/30 rounded-2xl transition-all duration-300" />
    </Link>
  );
}

interface ExperienceCategoriesSectionProps {
  cmsData?: CmsData;
}

export function ExperienceCategoriesSection({ cmsData }: ExperienceCategoriesSectionProps) {
  const t = useTranslations('home');
  const tNav = useTranslations('navigation');
  const locale = useLocale();

  // Use CMS categories if available, otherwise use fallback service types
  const categories: Array<{ id: string; name: string; image: string | null; link: string }> =
    cmsData?.categories && cmsData.categories.length > 0
      ? cmsData.categories.map((cat) => ({
          id: cat.id,
          name: cat.name,
          image: cat.image,
          link: cat.link,
        }))
      : fallbackServiceTypes.map((st) => ({
          id: st.id,
          name: tNav(st.labelKey),
          image: null, // Will use default GIFs
          link: `/listings?type=${st.slug}`,
        }));

  // Dynamic grid columns based on number of categories
  const getGridCols = (count: number) => {
    if (count === 3) return 'md:grid-cols-3';
    if (count === 5) return 'md:grid-cols-5';
    if (count >= 6) return 'md:grid-cols-3';
    return 'md:grid-cols-4'; // Default for 4 or fewer
  };

  return (
    <section className="py-16 md:py-20 bg-white">
      <div className="container mx-auto px-4">
        {/* Header */}
        <div className="text-center mb-10 md:mb-12">
          <h2 className="text-3xl md:text-4xl font-display font-bold text-primary uppercase tracking-wide mb-3">
            {cmsData?.title || t('experiences_title')}
          </h2>
          <p className="text-lg text-neutral-600">
            {cmsData?.subtitle || t('experiences_subtitle')}
          </p>
        </div>

        {/* Dynamic Grid */}
        <div className="max-w-6xl mx-auto">
          <div
            className={`grid grid-cols-1 sm:grid-cols-2 ${getGridCols(categories.length)} gap-4 md:gap-6`}
          >
            {categories.map((category) => (
              <CategoryCard key={category.id} category={category} locale={locale} />
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
