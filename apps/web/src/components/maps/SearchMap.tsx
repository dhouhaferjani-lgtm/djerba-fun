'use client';

import type { LatLngTuple } from 'leaflet';
import MapContainer from './MapContainer';
import MarkerPopup from './MarkerPopup';
import type { ListingSummary } from '@go-adventure/schemas';

interface SearchMapProps {
  listings: ListingSummary[];
  center?: LatLngTuple;
  zoom?: number;
  className?: string;
}

export default function SearchMap({
  listings,
  center = [46.2276, 2.2137], // Center of France
  zoom = 6,
  className,
}: SearchMapProps) {
  // Note: Marker clustering can be added later with leaflet.markercluster
  // For now, we render individual markers directly

  // TODO: The SearchMap component needs coordinates in the listing data
  // Current ListingSummary schema only has location.id and location.name
  // The backend should include coordinates when returning search results
  // For now, we'll use the default center until the backend provides coordinates

  const mapCenter: LatLngTuple = center;

  return (
    <MapContainer center={mapCenter} zoom={zoom} className={className}>
      {listings.map((listing) => {
        // TODO: Extract coordinates from listing when backend provides them
        // For now, all markers will be at the default position
        const position: LatLngTuple = [46.2276, 2.2137];

        return (
          <MarkerPopup
            key={listing.id}
            position={position}
            title={listing.title}
            imageUrl={listing.media[0]?.url}
            price={{
              amount: listing.pricing.displayPrice || listing.pricing.tndPrice || 0,
              currency: listing.pricing.displayCurrency || 'TND',
            }}
            slug={listing.slug}
            type="listing"
          />
        );
      })}
    </MapContainer>
  );
}
