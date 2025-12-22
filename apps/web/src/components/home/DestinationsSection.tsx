'use client';

import { useTranslations } from 'next-intl';
import Link from 'next/link';
import Image from 'next/image';
import { useLocale } from 'next-intl';
import { useQuery } from '@tanstack/react-query';
import { locationsApi, type Location } from '@/lib/api/client';

interface DestinationsSectionProps {
  destinations?: Location[];
}

async function getDestinations(): Promise<{ data: Location[] }> {
  return locationsApi.list();
}

export function DestinationsSection({
  destinations: initialDestinations,
}: DestinationsSectionProps) {
  const t = useTranslations('home');
  const locale = useLocale();

  // Fetch destinations from API
  const { data } = useQuery({
    queryKey: ['destinations'],
    queryFn: getDestinations,
    staleTime: 1000 * 60 * 60, // 1 hour
    initialData: initialDestinations ? { data: initialDestinations } : undefined,
  });

  const destinations = data?.data || [];

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
          {destinations.slice(0, 6).map((destination, index) => {
            // Create varied sizes for bento effect
            const isLarge = index === 0 || index === 3;
            const isTall = index === 1 || index === 4;

            // Fallback image if no image URL provided
            const imageUrl =
              destination.imageUrl ||
              `https://images.unsplash.com/photo-1590059390047-f5e617690a0b?w=800&q=80&sig=${destination.id}`;

            return (
              <Link
                key={destination.id}
                href={`/destinations/${destination.slug}` as any}
                className={`
                  relative rounded-2xl overflow-hidden group
                  ${isLarge ? 'md:col-span-2 md:row-span-2' : ''}
                  ${isTall ? 'row-span-2' : ''}
                `}
              >
                <Image
                  src={imageUrl}
                  alt={destination.name}
                  fill
                  sizes="(max-width: 768px) 50vw, (max-width: 1024px) 33vw, 25vw"
                  className="object-cover transition-transform duration-500 group-hover:scale-110"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent" />
                <div className="absolute bottom-0 left-0 right-0 p-6">
                  <h3 className="text-white font-bold text-xl md:text-2xl mb-1">
                    {destination.name}
                  </h3>
                  <p className="text-white/80 text-sm">
                    {destination.listingsCount}{' '}
                    {destination.listingsCount === 1 ? 'experience' : 'experiences'}
                  </p>
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
