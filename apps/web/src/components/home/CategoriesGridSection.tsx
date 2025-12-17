'use client';

import { useTranslations } from 'next-intl';
import Image from 'next/image';
import Link from 'next/link';

const categories = [
  {
    id: 'trail-running',
    name: 'Trail Running',
    count: 12,
    image: 'http://localhost:9002/go-adventure/categories/trail-running.jpg',
  },
  {
    id: 'hiking',
    name: 'Hiking & Trekking',
    count: 24,
    image: 'http://localhost:9002/go-adventure/categories/hiking.jpg',
  },
  {
    id: 'cycling',
    name: 'Cycling Tours',
    count: 18,
    image: 'http://localhost:9002/go-adventure/categories/cycling.jpg',
  },
  {
    id: 'cultural',
    name: 'Cultural Tours',
    count: 32,
    image: 'http://localhost:9002/go-adventure/categories/cultural.jpg',
  },
];

interface CategoriesGridSectionProps {
  locale: string;
}

export function CategoriesGridSection({ locale }: CategoriesGridSectionProps) {
  const t = useTranslations('home');

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
              className="group block overflow-hidden rounded-lg transition-shadow hover:shadow-xl"
            >
              {/* Image Area */}
              <div className="relative h-48 overflow-hidden">
                <Image
                  src={category.image}
                  alt={category.name}
                  fill
                  className="object-cover transition-all duration-300 group-hover:scale-110"
                />
                {/* Dark Overlay on Hover */}
                <div className="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors duration-300" />
              </div>

              {/* Footer */}
              <div className="bg-cream p-4 text-center">
                <h3 className="font-display font-bold text-lg text-neutral-900 mb-1">
                  {category.name}
                </h3>
                <p className="text-sm text-neutral-600">
                  {category.count} {category.count === 1 ? 'Package' : 'Packages'}
                </p>
              </div>
            </Link>
          ))}
        </div>
      </div>
    </section>
  );
}
