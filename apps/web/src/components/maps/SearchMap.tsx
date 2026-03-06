'use client';

import { useMemo } from 'react';
import type { LatLngTuple } from 'leaflet';
import MapContainer from './MapContainer';
import MarkerPopup from './MarkerPopup';
import type { ListingSummary } from '@djerba-fun/schemas';
import type { Locale } from '@/i18n/routing';

interface SearchMapProps {
  listings: ListingSummary[];
  locale?: Locale;
  center?: LatLngTuple;
  zoom?: number;
  className?: string;
}

export default function SearchMap({
  listings,
  locale = 'fr',
  center,
  zoom = 6,
  className,
}: SearchMapProps) {
  // Calculate map center based on listings' coordinates
  const mapCenter: LatLngTuple = useMemo(() => {
    if (center) return center;

    // Filter listings with valid coordinates
    const listingsWithCoords = listings.filter((l) => {
      const loc = l.location as any;
      return (
        loc?.latitude !== null &&
        loc?.latitude !== undefined &&
        loc?.longitude !== null &&
        loc?.longitude !== undefined &&
        !isNaN(loc.latitude) &&
        !isNaN(loc.longitude)
      );
    });

    if (listingsWithCoords.length === 0) {
      // Default to Tunisia center if no valid coordinates
      return [33.8869, 9.5375]; // Tunisia
    }

    // Calculate average center
    const avgLat =
      listingsWithCoords.reduce((sum, l) => sum + ((l.location as any).latitude as number), 0) /
      listingsWithCoords.length;
    const avgLng =
      listingsWithCoords.reduce((sum, l) => sum + ((l.location as any).longitude as number), 0) /
      listingsWithCoords.length;

    return [avgLat, avgLng];
  }, [listings, center]);

  return (
    <MapContainer center={mapCenter} zoom={zoom} className={className}>
      {listings.map((listing) => {
        const loc = listing.location as any;
        // Skip listings without valid coordinates
        if (
          !loc?.latitude ||
          !loc?.longitude ||
          loc.latitude === null ||
          loc.longitude === null ||
          isNaN(loc.latitude) ||
          isNaN(loc.longitude)
        ) {
          return null;
        }

        const position: LatLngTuple = [loc.latitude as number, loc.longitude as number];

        return (
          <MarkerPopup
            key={listing.id}
            position={position}
            title={listing.title}
            imageUrl={listing.media[0]?.url}
            price={{
              amount: listing.pricing.displayPrice || listing.pricing.tndPrice || 0,
              currency: listing.pricing.displayCurrency || 'EUR',
            }}
            slug={listing.slug}
            location={listing.location}
            locale={locale}
            type="listing"
          />
        );
      })}
    </MapContainer>
  );
}
