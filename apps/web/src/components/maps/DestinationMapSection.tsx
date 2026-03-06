'use client';

import dynamic from 'next/dynamic';
import type { Locale } from '@/i18n/routing';
import type { ListingSummary } from '@djerba-fun/schemas';

const SearchMap = dynamic(() => import('@/components/maps/SearchMap'), {
  ssr: false,
  loading: () => <div className="h-[500px] bg-neutral-100 animate-pulse rounded-lg" />,
});

interface DestinationMapSectionProps {
  listings: ListingSummary[];
  locale: Locale;
  center: [number, number];
}

export function DestinationMapSection({ listings, locale, center }: DestinationMapSectionProps) {
  return (
    <section className="py-8 bg-neutral-light">
      <div className="container mx-auto px-4">
        <h2 className="text-2xl font-bold mb-6">Explore on Map</h2>
        <div className="h-[500px] rounded-lg overflow-hidden shadow-lg">
          <SearchMap
            listings={listings}
            locale={locale}
            center={center}
            zoom={11}
            className="h-full"
          />
        </div>
      </div>
    </section>
  );
}
