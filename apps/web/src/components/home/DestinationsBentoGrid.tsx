'use client';

import { useTranslations } from 'next-intl';
import Image from 'next/image';
import Link from 'next/link';

const destinations = [
  {
    id: 'tozeur',
    name: 'Tozeur',
    gridClass: 'col-span-2 row-span-2',
    image: 'https://images.unsplash.com/photo-1551918120-9739cb430c6d?w=1200',
  },
  {
    id: 'douz',
    name: 'Douz',
    gridClass: 'col-span-1 row-span-1',
    image: 'https://images.unsplash.com/photo-1509099863731-ef4bff19e808?w=800',
  },
  {
    id: 'djerba',
    name: 'Djerba',
    gridClass: 'col-span-1 row-span-1',
    image: 'https://images.unsplash.com/photo-1548013146-72479768bada?w=800',
  },
  {
    id: 'tataouine',
    name: 'Tataouine',
    gridClass: 'col-span-2 row-span-1',
    image: 'https://images.unsplash.com/photo-1563308780-5fa633445448?w=1200',
  },
];

interface DestinationsBentoGridProps {
  locale: string;
}

export function DestinationsBentoGrid({ locale }: DestinationsBentoGridProps) {
  const t = useTranslations('home');

  return (
    <section className="py-20 bg-[#f5f0d1]">
      <div className="container mx-auto px-4">
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-display font-bold text-neutral-900 mb-4">
            Discover Tunisia
          </h2>
          <p className="text-lg text-neutral-600 max-w-2xl mx-auto">
            From ancient oases to stunning coastlines, explore the diversity of our destinations
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 auto-rows-[200px] gap-4">
          {destinations.map((destination) => (
            <Link
              key={destination.id}
              href={`/${locale}/listings?location=${destination.id}`}
              className={`group relative overflow-hidden rounded-lg ${destination.gridClass}`}
            >
              {/* Background Image */}
              <div className="absolute inset-0">
                <Image
                  src={destination.image}
                  alt={destination.name}
                  fill
                  className="object-cover transition-transform duration-500 group-hover:scale-110"
                />
                {/* Gradient Overlay at Bottom */}
                <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent" />
              </div>

              {/* Text - Bottom Left */}
              <div className="absolute bottom-0 left-0 p-6">
                <h3 className="text-2xl md:text-3xl font-display font-bold text-white">
                  {destination.name}
                </h3>
              </div>
            </Link>
          ))}
        </div>
      </div>
    </section>
  );
}
