'use client';

import { useTranslations, useLocale } from 'next-intl';
import Image from 'next/image';
import Link from 'next/link';

interface CmsData {
  enabled: boolean;
  title: string | null;
  subtitle: string | null;
}

// Service types with their translation keys
const serviceTypes = [
  { id: 'tour', slug: 'tour', labelKey: 'tours' },
  { id: 'nautical', slug: 'nautical', labelKey: 'nautical' },
  { id: 'accommodation', slug: 'accommodation', labelKey: 'accommodations' },
  { id: 'event', slug: 'event', labelKey: 'events' },
] as const;

type ServiceType = (typeof serviceTypes)[number];

// Placeholder images by service type slug (to be updated later)
const serviceTypeImages: Record<string, string> = {
  tour: '/images/experiences/island-tours.jpg',
  nautical: '/images/experiences/nautical-activities.jpg',
  accommodation: '/images/experiences/beach-relaxation.jpg',
  event: '/images/experiences/cultural-heritage.jpg',
};

// Default fallback image
const defaultFallbackImage = '/images/experiences/island-tours.jpg';

interface ServiceTypeCardProps {
  serviceType: ServiceType;
  locale: string;
  label: string;
}

function ServiceTypeCard({ serviceType, locale, label }: ServiceTypeCardProps) {
  const image = serviceTypeImages[serviceType.slug] || defaultFallbackImage;

  return (
    <Link
      href={`/${locale}/listings?type=${serviceType.slug}`}
      className="group relative block overflow-hidden rounded-2xl h-56 md:h-64"
    >
      {/* Background Image */}
      <Image
        src={image}
        alt={label}
        fill
        sizes="(max-width: 640px) 100vw, (max-width: 768px) 50vw, 25vw"
        className="object-cover transition-transform duration-500 group-hover:scale-110"
      />

      {/* Gradient Overlay */}
      <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent" />

      {/* Title */}
      <div className="absolute inset-0 flex items-center justify-center p-6">
        <h3 className="text-center font-display text-xl md:text-2xl font-bold text-white uppercase tracking-wide drop-shadow-lg">
          {label}
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

        {/* 4-Column Grid */}
        <div className="max-w-6xl mx-auto">
          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
            {serviceTypes.map((serviceType) => (
              <ServiceTypeCard
                key={serviceType.id}
                serviceType={serviceType}
                locale={locale}
                label={tNav(serviceType.labelKey)}
              />
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
