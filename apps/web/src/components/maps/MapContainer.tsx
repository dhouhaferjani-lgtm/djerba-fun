'use client';

import { useEffect, useState } from 'react';
import type { LatLngTuple } from 'leaflet';

interface MapContainerProps {
  center: LatLngTuple;
  zoom?: number;
  bounds?: LatLngTuple[];
  className?: string;
  children?: React.ReactNode;
}

export default function MapContainer({
  center,
  zoom = 13,
  bounds,
  className = 'h-96 w-full rounded-lg',
  children,
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

    // Load Leaflet CSS
    if (typeof document !== 'undefined') {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
      link.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
      link.crossOrigin = '';
      document.head.appendChild(link);
    }
  }, []);

  if (!MapComponent) {
    return (
      <div className={`${className} flex items-center justify-center bg-neutral-100`}>
        <div className="text-neutral-500">Loading map...</div>
      </div>
    );
  }

  return (
    <MapComponent center={center} zoom={zoom} bounds={bounds} className={className}>
      {children}
    </MapComponent>
  );
}
