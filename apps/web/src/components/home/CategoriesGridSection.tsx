'use client';

import { useTranslations } from 'next-intl';
import Image from 'next/image';
import Link from 'next/link';
import { useState, useEffect } from 'react';
import { useCategoryStats } from '@/lib/api/hooks';
import { MapPin, Calendar } from 'lucide-react';

interface CategoriesGridSectionProps {
  locale: string;
}

interface CategoryCardProps {
  id: 'tours' | 'events';
  nameKey: string;
  count: number;
  images: string[];
  href: string;
  icon: React.ReactNode;
}

function CategoryCard({ id, nameKey, count, images, href, icon }: CategoryCardProps) {
  const t = useTranslations('categories');
  const tCommon = useTranslations('common');
  const [currentImageIndex, setCurrentImageIndex] = useState(0);
  const [isTransitioning, setIsTransitioning] = useState(false);

  // Rotate images every 2 seconds
  useEffect(() => {
    if (images.length <= 1) return;

    const interval = setInterval(() => {
      setIsTransitioning(true);

      // After fade out, change image
      setTimeout(() => {
        setCurrentImageIndex((prev) => (prev + 1) % images.length);
        setIsTransitioning(false);
      }, 300); // Match CSS transition duration
    }, 2000);

    return () => clearInterval(interval);
  }, [images.length]);

  const currentImage = images[currentImageIndex] || images[0];

  return (
    <Link
      href={href as any}
      className="green-click-shadow group block overflow-hidden rounded-2xl transition-all duration-300 hover:shadow-xl bg-white border border-neutral-200"
    >
      {/* Image Area */}
      <div className="relative h-56 overflow-hidden">
        <Image
          src={currentImage}
          alt={t(nameKey)}
          fill
          sizes="(max-width: 768px) 100vw, 50vw"
          className={`object-cover transition-all duration-300 group-hover:scale-105 ${
            isTransitioning ? 'opacity-0' : 'opacity-100'
          }`}
        />
        {/* Gradient Overlay */}
        <div className="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent" />

        {/* Icon Badge */}
        <div className="absolute top-4 left-4 bg-white/90 backdrop-blur-sm rounded-full p-2 shadow-md">
          {icon}
        </div>

        {/* Image dots indicator */}
        {images.length > 1 && (
          <div className="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-1.5">
            {images.map((_, idx) => (
              <span
                key={idx}
                className={`w-1.5 h-1.5 rounded-full transition-all duration-300 ${
                  idx === currentImageIndex ? 'bg-white w-4' : 'bg-white/50'
                }`}
              />
            ))}
          </div>
        )}
      </div>

      {/* Content Footer */}
      <div className="bg-cream p-5">
        <h3 className="font-display font-bold text-xl text-neutral-900 mb-1">{t(nameKey)}</h3>
        <p className="text-sm text-neutral-600">
          {count} {count === 1 ? tCommon('package') : tCommon('packages')}
        </p>
      </div>
    </Link>
  );
}

// Skeleton loader for loading state
function CategoryCardSkeleton() {
  return (
    <div className="overflow-hidden rounded-2xl bg-white border border-neutral-200 animate-pulse">
      <div className="h-56 bg-neutral-200" />
      <div className="p-5 bg-cream">
        <div className="h-6 bg-neutral-300 rounded w-32 mb-2" />
        <div className="h-4 bg-neutral-200 rounded w-20" />
      </div>
    </div>
  );
}

export function CategoriesGridSection({ locale }: CategoriesGridSectionProps) {
  const t = useTranslations('home');
  const { data: stats, isLoading, error } = useCategoryStats();

  // Fallback images if API fails or returns empty arrays
  const fallbackImages = [
    'https://images.unsplash.com/photo-1551632811-561732d1e306?w=600&q=80',
    'https://images.unsplash.com/photo-1541625602330-2277a4c46182?w=600&q=80',
  ];

  const categories = [
    {
      id: 'tours' as const,
      nameKey: 'tours',
      count: stats?.tours.count ?? 0,
      images: stats?.tours.images?.length ? stats.tours.images : fallbackImages,
      href: `/${locale}/listings?type=tour`,
      icon: <MapPin className="h-5 w-5 text-primary" />,
    },
    {
      id: 'events' as const,
      nameKey: 'events',
      count: stats?.events.count ?? 0,
      images: stats?.events.images?.length ? stats.events.images : fallbackImages,
      href: `/${locale}/listings?type=event`,
      icon: <Calendar className="h-5 w-5 text-primary" />,
    },
  ];

  return (
    <section className="py-20 bg-white">
      <div className="container mx-auto px-4">
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-display font-bold text-neutral-900 mb-4">
            {t('categories_title')}
          </h2>
          <p className="text-lg text-neutral-600 max-w-2xl mx-auto">{t('categories_subtitle')}</p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
          {isLoading ? (
            <>
              <CategoryCardSkeleton />
              <CategoryCardSkeleton />
            </>
          ) : (
            categories.map((category) => <CategoryCard key={category.id} {...category} />)
          )}
        </div>

        {error && (
          <p className="text-center text-sm text-neutral-500 mt-4">
            Unable to load latest data. Showing cached information.
          </p>
        )}
      </div>
    </section>
  );
}
