'use client';

import { useEffect, useState } from 'react';
import type { LatLngTuple } from 'leaflet';
import MapContainer from './MapContainer';
import MarkerPopup from './MarkerPopup';
import type { ItineraryStop } from '@go-adventure/schemas';

interface ListingMapProps {
  center: LatLngTuple;
  title: string;
  imageUrl?: string;
  itinerary?: ItineraryStop[];
  className?: string;
}

interface RouteProps {
  stops: ItineraryStop[];
}

export default function ListingMap({
  center,
  title,
  imageUrl,
  itinerary,
  className,
}: ListingMapProps) {
  const [RouteComponent, setRouteComponent] = useState<React.ComponentType<RouteProps> | null>(
    null
  );

  useEffect(() => {
    if (itinerary && itinerary.length > 0) {
      import('react-leaflet').then((module) => {
        const { Polyline } = module;

        const Component = ({ stops }: { stops: ItineraryStop[] }) => {
          const positions: LatLngTuple[] = stops.map((stop) => [stop.lat, stop.lng]);
          return (
            <Polyline
              positions={positions}
              pathOptions={{
                color: '#0D642E',
                weight: 3,
                opacity: 0.8,
              }}
            />
          );
        };

        setRouteComponent(() => Component);
      });
    }
  }, [itinerary]);

  return (
    <MapContainer center={center} zoom={13} className={className}>
      {/* Main listing marker */}
      <MarkerPopup position={center} title={title} imageUrl={imageUrl} type="listing" />

      {/* Itinerary route and markers */}
      {itinerary && itinerary.length > 0 && (
        <>
          {RouteComponent && <RouteComponent stops={itinerary} />}
          {itinerary.map((stop, index) => (
            <MarkerPopup
              key={stop.id}
              position={[stop.lat, stop.lng]}
              title={typeof stop.title === 'string' ? stop.title : stop.title.en}
              description={
                stop.description
                  ? typeof stop.description === 'string'
                    ? stop.description
                    : stop.description.en
                  : undefined
              }
              type={index === 0 ? 'start' : index === itinerary.length - 1 ? 'end' : 'waypoint'}
            />
          ))}
        </>
      )}
    </MapContainer>
  );
}
