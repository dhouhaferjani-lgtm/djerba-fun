'use client';

/**
 * Performance Optimization: Dynamic import wrapper for ElevationProfile
 *
 * This component lazy-loads the ElevationProfile chart component which includes
 * complex SVG rendering and calculations. Loading on demand improves initial page load.
 *
 * Benefits:
 * - Reduces initial bundle size
 * - Only loads when elevation data is available
 * - Improves Time to Interactive (TTI)
 */

import dynamic from 'next/dynamic';
import type { ElevationProfile as ElevationProfileType, ItineraryStop } from '@djerba-fun/schemas';

interface ElevationProfileProps {
  profile: ElevationProfileType;
  checkpoints?: ItineraryStop[];
  locale?: string;
  className?: string;
}

// Loading skeleton that matches the component's layout
const ElevationLoading = ({ className = '' }: { className?: string }) => (
  <div className={`space-y-6 ${className}`}>
    {/* Stats skeleton */}
    <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
      {[1, 2, 3, 4].map((i) => (
        <div key={i} className="bg-white rounded-lg border border-neutral-200 p-4 animate-pulse">
          <div className="h-4 bg-neutral-200 rounded w-24 mb-2"></div>
          <div className="h-8 bg-neutral-300 rounded w-16"></div>
        </div>
      ))}
    </div>
    {/* Chart skeleton */}
    <div className="relative rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
      <div className="h-6 bg-neutral-200 rounded w-48 mb-4"></div>
      <div className="h-64 bg-neutral-100 rounded animate-pulse"></div>
    </div>
  </div>
);

// Dynamic import with custom loading component
const ElevationProfile = dynamic(() => import('./ElevationProfile'), {
  ssr: true,
  loading: () => <ElevationLoading />,
});

export default function ElevationProfileDynamic(props: ElevationProfileProps) {
  return <ElevationProfile {...props} />;
}
