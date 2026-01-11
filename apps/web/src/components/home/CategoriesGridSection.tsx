'use client';

import { useTranslations } from 'next-intl';
import Image from 'next/image';
import Link from 'next/link';

const categories = [
  {
    id: 'trail-running',
    nameKey: 'trail-running',
    count: 12,
    image: 'https://images.unsplash.com/photo-1551632811-561732d1e306?w=600&q=80',
  },
  {
    id: 'hiking',
    nameKey: 'hiking',
    count: 24,
    image: 'https://images.unsplash.com/photo-1551632811-561732d1e306?w=600&q=80',
  },
  {
    id: 'cycling',
    nameKey: 'cycling',
    count: 18,
    image: 'https://images.unsplash.com/photo-1541625602330-2277a4c46182?w=600&q=80',
  },
  {
    id: 'cultural',
    nameKey: 'cultural',
    count: 32,
    image: 'https://images.unsplash.com/photo-1590492106698-05dc0e19fb26?w=600&q=80',
  },
];

interface CategoriesGridSectionProps {
  locale: string;
}

export function CategoriesGridSection({ locale }: CategoriesGridSectionProps) {
  const t = useTranslations('home');
  const tCategories = useTranslations('categories');
  const tCommon = useTranslations('common');

  return (
    <section className="py-20 bg-white">
      <div className="container mx-auto px-4">
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-display font-bold text-neutral-900 mb-4">
            {t('categories_title')}
          </h2>
          <p className="text-lg text-neutral-600 max-w-2xl mx-auto">{t('categories_subtitle')}</p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {categories.map((category) => (
            <Link
              key={category.id}
              href={`/${locale}/listings?category=${category.id}`}
              className="green-click-shadow group block overflow-hidden rounded-lg transition-all hover:shadow-xl"
            >
              {/* Image Area */}
              <div className="relative h-48 overflow-hidden">
                <Image
                  src={category.image}
                  alt={tCategories(category.nameKey)}
                  fill
                  sizes="(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 25vw"
                  className="object-cover transition-all duration-300 group-hover:scale-110"
                />
                {/* Dark Overlay on Hover */}
                <div className="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors duration-300" />
              </div>

              {/* Footer */}
              <div className="bg-cream p-4 text-center">
                <h3 className="font-display font-bold text-lg text-neutral-900 mb-1">
                  {tCategories(category.nameKey)}
                </h3>
                <p className="text-sm text-neutral-600">
                  {category.count} {category.count === 1 ? tCommon('package') : tCommon('packages')}
                </p>
              </div>
            </Link>
          ))}
        </div>
      </div>
    </section>
  );
}
