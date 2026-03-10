'use client';

import 'leaflet/dist/leaflet.css'; // Import CSS directly to avoid race condition

import { useEffect, useState } from 'react';
import type { LatLngTuple } from 'leaflet';

interface MapContainerProps {
  center: LatLngTuple;
  zoom?: number;
  bounds?: LatLngTuple[];
  className?: string;
  children?: React.ReactNode;
  'data-testid'?: string;
}

export default function MapContainer({
  center,
  zoom = 13,
  bounds,
  className = 'h-96 w-full rounded-lg',
  children,
  'data-testid': dataTestId,
}: MapContainerProps) {
  const [MapComponent, setMapComponent] = useState<React.ComponentType<MapContainerProps> | null>(
    null
  );

  useEffect(() => {
    // Lazy load Leaflet to avoid SSR issues
    Promise.all([import('react-leaflet'), import('leaflet')]).then(
      ([reactLeafletModule, leafletModule]) => {
        const { MapContainer: LeafletMap, TileLayer } = reactLeafletModule;
        const L = leafletModule.default;

        const Component = ({ center, zoom, bounds, className, children }: MapContainerProps) => {
          // Use bounds for initial viewport if provided, otherwise center/zoom
          const useBounds = bounds && bounds.length >= 2;
          const leafletBounds = useBounds
            ? L.latLngBounds(bounds.map((b) => L.latLng(b[0], b[1])))
            : undefined;

          return (
            <LeafletMap
              {...(leafletBounds
                ? {
                    bounds: leafletBounds,
                    boundsOptions: { padding: [50, 50] as [number, number] },
                  }
                : { center, zoom })}
              scrollWheelZoom={false}
              className={className}
              style={{ height: '100%', width: '100%' }}
            >
              <TileLayer
                attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
              />
              {children}
            </LeafletMap>
          );
        };

        setMapComponent(() => Component);
      }
    );
  }, []);

  if (!MapComponent) {
    return (
      <div
        className={`${className} flex items-center justify-center bg-neutral-100`}
        data-testid={dataTestId}
      >
        <div className="text-neutral-500">Loading map...</div>
      </div>
    );
  }

  return (
    <div data-testid={dataTestId}>
      <MapComponent center={center} zoom={zoom} bounds={bounds} className={className}>
        {children}
      </MapComponent>
    </div>
  );
}
