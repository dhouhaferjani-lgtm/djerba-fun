'use client';

import { useTranslations } from 'next-intl';
import Link from 'next/link';
import Image from 'next/image';
import { useLocale } from 'next-intl';

interface Destination {
  slug: string;
  name: string;
  image: string;
  count: number;
}

interface DestinationsSectionProps {
  destinations?: Destination[];
}

const defaultDestinations: Destination[] = [
  {
    slug: 'djerba',
    name: 'Djerba',
    image: 'http://localhost:9002/go-adventure/featured/djerba-island.jpg',
    count: 12,
  },
  {
    slug: 'sahara-desert',
    name: 'Sahara Desert',
    image: 'http://localhost:9002/go-adventure/featured/sahara-desert.jpg',
    count: 8,
  },
  {
    slug: 'tunis',
    name: 'Tunis',
    image: 'https://images.unsplash.com/photo-1590492106698-05dc0e19fb26?w=600',
    count: 15,
  },
  {
    slug: 'sidi-bou-said',
    name: 'Sidi Bou Said',
    image: 'https://images.unsplash.com/photo-1568797629192-789acf8e4df3?w=600',
    count: 6,
  },
  {
    slug: 'tozeur',
    name: 'Tozeur',
    image: 'http://localhost:9002/go-adventure/featured/mountain-trek.jpg',
    count: 5,
  },
  {
    slug: 'carthage',
    name: 'Carthage',
    image: 'https://images.unsplash.com/photo-1564507592333-c60657eea523?w=600',
    count: 4,
  },
];

export function DestinationsSection({
  destinations = defaultDestinations,
}: DestinationsSectionProps) {
  const t = useTranslations('home');
  const locale = useLocale();

  return (
    <section className="py-20 bg-neutral-light">
      <div className="container mx-auto px-4">
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-bold text-neutral-darker mb-4">
            {t('destinations_title')}
          </h2>
          <p className="text-lg text-neutral-dark max-w-2xl mx-auto">
            {t('destinations_subtitle')}
          </p>
        </div>

        {/* Bento grid layout */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 auto-rows-[200px]">
          {destinations.map((destination, index) => {
            // Create varied sizes for bento effect
            const isLarge = index === 0 || index === 3;
            const isTall = index === 1 || index === 4;

            return (
              <Link
                key={destination.slug}
                href={`/${locale}/listings?location=${destination.slug}`}
                className={`
                  relative rounded-2xl overflow-hidden group
                  ${isLarge ? 'md:col-span-2 md:row-span-2' : ''}
                  ${isTall ? 'row-span-2' : ''}
                `}
              >
                <Image
                  src={destination.image}
                  alt={destination.name}
                  fill
                  className="object-cover transition-transform duration-500 group-hover:scale-110"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent" />
                <div className="absolute bottom-0 left-0 right-0 p-6">
                  <h3 className="text-white font-bold text-xl md:text-2xl mb-1">
                    {destination.name}
                  </h3>
                  <p className="text-white/80 text-sm">{destination.count} experiences</p>
                </div>
                <div className="absolute inset-0 border-2 border-white/0 group-hover:border-white/30 rounded-2xl transition-colors" />
              </Link>
            );
          })}
        </div>
      </div>
    </section>
  );
}
