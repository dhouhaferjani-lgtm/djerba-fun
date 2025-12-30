'use client';

/**
 * Performance Optimization: Dynamic import wrapper for MapContainer
 *
 * This component lazy-loads the heavy Leaflet map component only when needed,
 * reducing initial bundle size and improving page load performance.
 *
 * Benefits:
 * - Reduces initial JavaScript bundle by ~100KB
 * - Leaflet CSS loaded on demand
 * - Better Time to Interactive (TTI)
 */

import dynamic from 'next/dynamic';
import type { LatLngTuple } from 'leaflet';

interface MapContainerProps {
  center: LatLngTuple;
  zoom?: number;
  className?: string;
  children?: React.ReactNode;
}

// Loading component with proper styling
const MapLoading = ({ className = 'h-96 w-full rounded-lg' }: { className?: string }) => (
  <div className={`${className} flex items-center justify-center bg-neutral-100 animate-pulse`}>
    <div className="flex flex-col items-center gap-2">
      <div className="h-12 w-12 rounded-full border-4 border-primary-200 border-t-primary-600 animate-spin"></div>
      <div className="text-sm text-neutral-500">Loading map...</div>
    </div>
  </div>
);

// Dynamic import with SSR disabled and custom loading component
const MapContainer = dynamic(() => import('./MapContainer'), {
  ssr: false,
  loading: () => <MapLoading />,
});

export default function MapContainerDynamic(props: MapContainerProps) {
  return <MapContainer {...props} />;
}
