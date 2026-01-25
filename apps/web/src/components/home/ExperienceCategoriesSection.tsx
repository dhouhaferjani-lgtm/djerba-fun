'use client';

import { useTranslations, useLocale } from 'next-intl';
import Image from 'next/image';
import Link from 'next/link';
import { useActivityTypes } from '@/lib/api/hooks';
import type { ActivityType } from '@go-adventure/schemas';

// Fallback images by slug (until real images are provided)
const fallbackImages: Record<string, string> = {
  'cultural-expeditions': 'https://images.unsplash.com/photo-1548013146-72479768bada?w=800&q=80',
  'corporate-sports': 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=800&q=80',
  'mountain-biking': 'https://images.unsplash.com/photo-1541625602330-2277a4c46182?w=800&q=80',
  'water-activities': 'https://images.unsplash.com/photo-1500514966906-fe245eea9344?w=800&q=80',
  'trail-trekking': 'https://images.unsplash.com/photo-1551632811-561732d1e306?w=800&q=80',
};

// Default fallback image if slug not in map
const defaultFallbackImage =
  'https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=800&q=80';

// Fallback activity types when API is unavailable (matches go-adventure.net old site)
const fallbackActivityTypes: ActivityType[] = [
  {
    id: '00000000-0000-0000-0000-000000000001',
    slug: 'cultural-expeditions',
    name: { en: 'Cultural Expeditions', fr: 'Expéditions Culturelles' },
    description: {
      en: 'Discover local culture, heritage sites, and traditional customs',
      fr: 'Découvrez la culture locale, les sites patrimoniaux et les coutumes traditionnelles',
    },
    icon: 'heroicon-o-building-library',
    color: '#8B4513',
    displayOrder: 1,
    isActive: true,
    listingsCount: 0,
  },
  {
    id: '00000000-0000-0000-0000-000000000002',
    slug: 'corporate-sports',
    name: { en: 'Corporate & Team Building Stays', fr: 'Séjours Corporate & Team Building' },
    description: {
      en: 'Team building, corporate retreats, and sports-focused group experiences',
      fr: "Team building, séminaires d'entreprise et expériences de groupe axées sur le sport",
    },
    icon: 'heroicon-o-building-office',
    color: '#4169E1',
    displayOrder: 2,
    isActive: true,
    listingsCount: 0,
  },
  {
    id: '00000000-0000-0000-0000-000000000003',
    slug: 'mountain-biking',
    name: { en: 'Road & Mountain Biking', fr: 'Vélo de Route & de Montagne' },
    description: {
      en: 'Explore scenic trails and paths on two wheels',
      fr: 'Explorez des sentiers et des chemins pittoresques à vélo',
    },
    icon: 'heroicon-o-sparkles',
    color: '#228B22',
    displayOrder: 3,
    isActive: true,
    listingsCount: 0,
  },
  {
    id: '00000000-0000-0000-0000-000000000004',
    slug: 'water-activities',
    name: { en: 'Water Activities & Sports', fr: 'Activités & Sports Nautiques' },
    description: {
      en: 'Enjoy water sports, diving, sailing, and coastal adventures',
      fr: 'Profitez des sports nautiques, de la plongée, de la voile et des aventures côtières',
    },
    icon: 'heroicon-o-lifebuoy',
    color: '#1E90FF',
    displayOrder: 4,
    isActive: true,
    listingsCount: 0,
  },
  {
    id: '00000000-0000-0000-0000-000000000005',
    slug: 'trail-trekking',
    name: { en: 'Trail Running, Hiking & Trekking', fr: 'Trail Running, Randonnée & Trekking' },
    description: {
      en: 'Hiking adventures through mountains, deserts, and natural landscapes',
      fr: 'Aventures de randonnée à travers les montagnes, les déserts et les paysages naturels',
    },
    icon: 'heroicon-o-map',
    color: '#0D642E',
    displayOrder: 5,
    isActive: true,
    listingsCount: 0,
  },
];

interface CategoryCardProps {
  activityType: ActivityType;
  locale: string;
  size: 'large' | 'small';
}

function CategoryCard({ activityType, locale, size }: CategoryCardProps) {
  const image = fallbackImages[activityType.slug] || defaultFallbackImage;
  // Extract localized name with proper typing for translatable field
  const nameObj = activityType.name as {
    en?: string;
    fr?: string;
    [key: string]: string | undefined;
  };
  const title = nameObj[locale] || nameObj.en || activityType.slug;

  return (
    <Link
      href={`/${locale}/listings?activity_type=${activityType.slug}`}
      className={`group relative block overflow-hidden rounded-2xl ${
        size === 'large' ? 'h-72 md:h-80' : 'h-56 md:h-64'
      }`}
    >
      {/* Background Image */}
      <Image
        src={image}
        alt={title}
        fill
        sizes={
          size === 'large' ? '(max-width: 768px) 100vw, 50vw' : '(max-width: 768px) 100vw, 33vw'
        }
        className="object-cover transition-transform duration-500 group-hover:scale-110"
      />

      {/* Gradient Overlay */}
      <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent" />

      {/* Title */}
      <div className="absolute inset-0 flex items-center justify-center p-6">
        <h3 className="text-center font-display text-xl md:text-2xl font-bold text-white uppercase tracking-wide drop-shadow-lg">
          {title}
        </h3>
      </div>

      {/* Hover Effect Border */}
      <div className="absolute inset-0 border-4 border-transparent group-hover:border-white/30 rounded-2xl transition-all duration-300" />
    </Link>
  );
}

function LoadingSkeleton() {
  return (
    <section className="py-16 md:py-20 bg-white">
      <div className="container mx-auto px-4">
        {/* Header Skeleton */}
        <div className="text-center mb-10 md:mb-12">
          <div className="h-10 w-64 bg-neutral-200 rounded-lg mx-auto mb-3 animate-pulse" />
          <div className="h-6 w-80 bg-neutral-200 rounded-lg mx-auto animate-pulse" />
        </div>

        {/* Grid Skeleton */}
        <div className="max-w-6xl mx-auto">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mb-4 md:mb-6">
            <div className="h-72 md:h-80 bg-neutral-200 rounded-2xl animate-pulse" />
            <div className="h-72 md:h-80 bg-neutral-200 rounded-2xl animate-pulse" />
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
            <div className="h-56 md:h-64 bg-neutral-200 rounded-2xl animate-pulse" />
            <div className="h-56 md:h-64 bg-neutral-200 rounded-2xl animate-pulse" />
            <div className="h-56 md:h-64 bg-neutral-200 rounded-2xl animate-pulse" />
          </div>
        </div>
      </div>
    </section>
  );
}

export function ExperienceCategoriesSection() {
  const t = useTranslations('home');
  const locale = useLocale();
  const { data: activityTypes, isLoading } = useActivityTypes();

  // Show loading skeleton while fetching
  if (isLoading) {
    return <LoadingSkeleton />;
  }

  // Use API data if available, otherwise use fallback (prevents regression when API is down)
  const dataSource =
    activityTypes && activityTypes.length > 0 ? activityTypes : fallbackActivityTypes;

  // Explicitly sort by displayOrder and take first 5 (defensive - API should already be sorted)
  const categories = [...dataSource].sort((a, b) => a.displayOrder - b.displayOrder).slice(0, 5);

  // Ensure we have enough categories for the layout (fallback guarantees 5)
  if (categories.length < 2) {
    return null;
  }

  return (
    <section className="py-16 md:py-20 bg-white">
      <div className="container mx-auto px-4">
        {/* Header */}
        <div className="text-center mb-10 md:mb-12">
          <h2 className="text-3xl md:text-4xl font-display font-bold text-primary uppercase tracking-wide mb-3">
            {t('experiences_title')}
          </h2>
          <p className="text-lg text-neutral-600">{t('experiences_subtitle')}</p>
        </div>

        {/* Bento Grid */}
        <div className="max-w-6xl mx-auto">
          {/* Top Row - 2 Large Cards */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mb-4 md:mb-6">
            {categories[0] && (
              <CategoryCard activityType={categories[0]} locale={locale} size="large" />
            )}
            {categories[1] && (
              <CategoryCard activityType={categories[1]} locale={locale} size="large" />
            )}
          </div>

          {/* Bottom Row - Up to 3 Smaller Cards */}
          {categories.length > 2 && (
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
              {categories[2] && (
                <CategoryCard activityType={categories[2]} locale={locale} size="small" />
              )}
              {categories[3] && (
                <CategoryCard activityType={categories[3]} locale={locale} size="small" />
              )}
              {categories[4] && (
                <CategoryCard activityType={categories[4]} locale={locale} size="small" />
              )}
            </div>
          )}
        </div>
      </div>
    </section>
  );
}
