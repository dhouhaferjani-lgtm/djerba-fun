/**
 * Performance-Optimized Components Export
 *
 * This file provides easy access to all performance-optimized components.
 * Use these versions instead of the originals for better performance.
 *
 * Usage:
 * import { MapContainerDynamic, BookingWizardDynamic } from '@/components/optimized';
 */

// Dynamic (lazy-loaded) components
export { default as MapContainerDynamic } from '../maps/MapContainerDynamic';
export { default as ElevationProfileDynamic } from '../itinerary/ElevationProfileDynamic';
export { default as BookingWizardDynamic } from '../booking/BookingWizardDynamic';

// Memoized components (already optimized, exported for reference)
export { ListingCard } from '../molecules/ListingCard';
export { PriceDisplay } from '../molecules/PriceDisplay';
export { default as ReviewCard } from '../reviews/ReviewCard';

// Skeleton components for loading states
export {
  Skeleton,
  SkeletonText,
  SkeletonCard,
  SkeletonAvatar,
  SkeletonButton,
} from '../atoms/Skeleton';
