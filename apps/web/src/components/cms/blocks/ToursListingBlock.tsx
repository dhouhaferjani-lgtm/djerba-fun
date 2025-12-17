'use client';

import { useEffect, useState } from 'react';
import { useLocale } from 'next-intl';
import { ToursListingBlockData } from '@/types/cms';
import { listingsApi } from '@/lib/api/client';
import type { ListingSummary } from '@go-adventure/schemas';
import { ListingCard } from '@/components/molecules/ListingCard';

export function ToursListingBlock({
  listing_type,
  count,
  sort_by,
  style = 'grid',
}: ToursListingBlockData) {
  const locale = useLocale();
  const [listings, setListings] = useState<ListingSummary[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function fetchListings() {
      try {
        setLoading(true);

        // Build query parameters
        const params: any = {
          limit: count,
        };

        // Filter by listing type if not 'all'
        if (listing_type !== 'all') {
          params.serviceType = listing_type;
        }

        // Apply sorting
        if (sort_by === 'price') {
          params.sort = 'price';
          params.order = 'asc';
        } else if (sort_by === '-price') {
          params.sort = 'price';
          params.order = 'desc';
        } else if (sort_by === 'title') {
          params.sort = 'title';
          params.order = 'asc';
        } else {
          params.sort = 'created_at';
          params.order = 'desc';
        }

        const response = await listingsApi.search(params);
        setListings(response.data);
      } catch (error) {
        console.error('Failed to fetch listings:', error);
      } finally {
        setLoading(false);
      }
    }

    fetchListings();
  }, [listing_type, count, sort_by]);

  if (loading) {
    return (
      <div className="tours-listing-block">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {Array.from({ length: count }).map((_, i) => (
            <div key={i} className="animate-pulse bg-gray-200 h-96 rounded-lg" />
          ))}
        </div>
      </div>
    );
  }

  if (!listings || listings.length === 0) {
    return (
      <div className="tours-listing-block">
        <p className="text-center text-gray-500">No tours or events available.</p>
      </div>
    );
  }

  if (style === 'list') {
    return (
      <div className="tours-listing-block space-y-4">
        {listings.map((listing) => (
          <div key={listing.id} className="flex gap-4 border-b pb-4">
            <ListingCard listing={listing} locale={locale} />
          </div>
        ))}
      </div>
    );
  }

  // Grid or carousel (carousel would need additional library like swiper)
  return (
    <div className={`tours-listing-block ${style === 'carousel' ? 'overflow-x-auto' : ''}`}>
      <div
        className={
          style === 'carousel'
            ? 'flex gap-6'
            : 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6'
        }
      >
        {listings.map((listing) => (
          <div key={listing.id} className={style === 'carousel' ? 'flex-shrink-0 w-80' : ''}>
            <ListingCard listing={listing} locale={locale} />
          </div>
        ))}
      </div>
    </div>
  );
}
