'use client';

import { useTranslations } from 'next-intl';
import { useLocale } from 'next-intl';
import Image from 'next/image';
import Link from 'next/link';

interface ExperienceCategory {
  key: string;
  image: string;
  href: string;
}

const categories: ExperienceCategory[] = [
  {
    key: 'cultural',
    // Tunisian fort/medina style image
    image: 'https://images.unsplash.com/photo-1548013146-72479768bada?w=800&q=80',
    href: '/listings?tags=cultural',
  },
  {
    key: 'corporate',
    // Team building / group activities
    image: 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=800&q=80',
    href: '/listings?tags=corporate',
  },
  {
    key: 'cycling',
    // Road/mountain biking
    image: 'https://images.unsplash.com/photo-1541625602330-2277a4c46182?w=800&q=80',
    href: '/listings?tags=cycling',
  },
  {
    key: 'water_sports',
    // Sailing/nautical activities at sunset
    image: 'https://images.unsplash.com/photo-1500514966906-fe245eea9344?w=800&q=80',
    href: '/listings?tags=water-sports',
  },
  {
    key: 'hiking',
    // Trail running / trekking in mountains
    image: 'https://images.unsplash.com/photo-1551632811-561732d1e306?w=800&q=80',
    href: '/listings?tags=hiking,trekking',
  },
];

interface CategoryCardProps {
  category: ExperienceCategory;
  locale: string;
  size: 'large' | 'small';
}

function CategoryCard({ category, locale, size }: CategoryCardProps) {
  const t = useTranslations('home');

  return (
    <Link
      href={`/${locale}${category.href}`}
      className={`group relative block overflow-hidden rounded-2xl ${
        size === 'large' ? 'h-72 md:h-80' : 'h-56 md:h-64'
      }`}
    >
      {/* Background Image */}
      <Image
        src={category.image}
        alt={t(`experience_${category.key}`)}
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
          {t(`experience_${category.key}`)}
        </h3>
      </div>

      {/* Hover Effect Border */}
      <div className="absolute inset-0 border-4 border-transparent group-hover:border-white/30 rounded-2xl transition-all duration-300" />
    </Link>
  );
}

export function ExperienceCategoriesSection() {
  const t = useTranslations('home');
  const locale = useLocale();

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
            <CategoryCard category={categories[0]} locale={locale} size="large" />
            <CategoryCard category={categories[1]} locale={locale} size="large" />
          </div>

          {/* Bottom Row - 3 Smaller Cards */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
            <CategoryCard category={categories[2]} locale={locale} size="small" />
            <CategoryCard category={categories[3]} locale={locale} size="small" />
            <CategoryCard category={categories[4]} locale={locale} size="small" />
          </div>
        </div>
      </div>
    </section>
  );
}
