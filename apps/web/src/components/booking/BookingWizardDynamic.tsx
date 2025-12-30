'use client';

/**
 * Performance Optimization: Dynamic import wrapper for BookingWizard
 *
 * BookingWizard is a complex multi-step form with validation, state management,
 * and API interactions. Lazy loading reduces initial page load time.
 *
 * Benefits:
 * - Reduces initial bundle by splitting form logic
 * - Only loads when user initiates booking
 * - Improves Time to Interactive (TTI)
 */

import dynamic from 'next/dynamic';
import type {
  BookingHold,
  Booking,
  ListingSummary,
  AvailabilitySlot,
  ListingExtraForBooking,
} from '@go-adventure/schemas';

interface BookingWizardProps {
  hold: BookingHold;
  listing: ListingSummary;
  slot: AvailabilitySlot;
  availableExtras?: ListingExtraForBooking[];
  onExpired?: () => void;
}

// Loading skeleton that matches wizard layout
const WizardLoading = () => (
  <div className="py-8">
    {/* Hold timer skeleton */}
    <div className="mb-6 bg-primary-50 border border-primary-200 rounded-lg p-4">
      <div className="h-4 bg-primary-200 rounded w-48 animate-pulse"></div>
    </div>
    {/* Form skeleton */}
    <div className="bg-white rounded-lg shadow-sm border border-neutral-200 p-6 space-y-4">
      <div className="h-6 bg-neutral-200 rounded w-32 mb-4"></div>
      <div className="space-y-3">
        {[1, 2, 3].map((i) => (
          <div key={i}>
            <div className="h-4 bg-neutral-200 rounded w-24 mb-2"></div>
            <div className="h-10 bg-neutral-100 rounded"></div>
          </div>
        ))}
      </div>
      <div className="h-10 bg-neutral-200 rounded w-full mt-6 animate-pulse"></div>
    </div>
  </div>
);

// Dynamic import with custom loading component
const BookingWizard = dynamic(
  () => import('./BookingWizard').then((mod) => ({ default: mod.BookingWizard })),
  {
    ssr: false,
    loading: () => <WizardLoading />,
  }
);

export default function BookingWizardDynamic(props: BookingWizardProps) {
  return <BookingWizard {...props} />;
}
