'use client';

import { useTranslations } from 'next-intl';
import Image from 'next/image';
import Link from 'next/link';

const categories = [
  {
    id: 'trail-running',
    name: 'Trail Running',
    count: 12,
    image: 'https://images.unsplash.com/photo-1476480862126-209bfaa8edc8?w=800',
  },
  {
    id: 'hiking',
    name: 'Hiking & Trekking',
    count: 24,
    image: 'https://images.unsplash.com/photo-1551632811-561732d1e306?w=800',
  },
  {
    id: 'cycling',
    name: 'Cycling Tours',
    count: 18,
    image: 'https://images.unsplash.com/photo-1541625602330-2277a4c46182?w=800',
  },
  {
    id: 'cultural',
    name: 'Cultural Tours',
    count: 32,
    image: 'https://images.unsplash.com/photo-1484199316225-b0f50e1b1e6e?w=800',
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
            Explore by Activity
          </h2>
          <p className="text-lg text-neutral-600 max-w-2xl mx-auto">
            From desert marathons to cultural immersions, find your perfect adventure
          </p>
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
              <div className="bg-[#fcfaf2] p-4 text-center">
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
