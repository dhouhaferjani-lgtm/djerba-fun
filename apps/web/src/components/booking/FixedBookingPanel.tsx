'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { Button } from '@go-adventure/ui';
import {
  Calendar,
  CheckCircle,
  X,
  Clock,
  Users,
  Shield,
  Smartphone,
  AlertCircle,
  TrendingUp,
} from 'lucide-react';
import { PriceDisplay } from '@/components/molecules/PriceDisplay';
import type { Listing, AvailabilitySlot } from '@go-adventure/schemas';
import { isToday, isTomorrow, parseISO } from 'date-fns';

interface FixedBookingPanelProps {
  listing: Listing;
  availabilityData?: AvailabilitySlot[];
  hasActualReviews?: boolean;
  children?: React.ReactNode;
}

// Helper function to get cancellation message
function getCancellationMessage(listing: Listing): string | null {
  if (!listing.cancellationPolicy) return null;

  const policy = listing.cancellationPolicy;

  if (policy.type === 'flexible') {
    return 'Free cancellation up to 24h before';
  } else if (policy.type === 'moderate') {
    return 'Free cancellation up to 48h before';
  } else if (policy.type === 'strict') {
    return 'Cancellation with restrictions';
  }

  return null;
}

// Helper function to get urgency message based on availability and reviews
function getUrgencyMessage(
  listing: Listing,
  availabilityData?: AvailabilitySlot[],
  hasActualReviews?: boolean
): { message: string; variant: 'warning' | 'success' | 'info' } | null {
  // Priority 1: Availability-based urgency (real-time scarcity)
  if (availabilityData && availabilityData.length > 0) {
    // Check for today's slots with low capacity
    const todaySlots = availabilityData.filter(
      (slot) =>
        isToday(parseISO(slot.start)) && (slot.status === 'available' || slot.status === 'limited')
    );
    const lowCapacityToday = todaySlots.find(
      (slot) => (slot.remainingCapacity ?? slot.capacity) <= 3
    );

    if (lowCapacityToday) {
      const remaining = lowCapacityToday.remainingCapacity ?? lowCapacityToday.capacity;
      return {
        message: `⚠️ Only ${remaining} spot${remaining !== 1 ? 's' : ''} left today!`,
        variant: 'warning',
      };
    }

    // Check for tomorrow's slots with low capacity
    const tomorrowSlots = availabilityData.filter(
      (slot) =>
        isTomorrow(parseISO(slot.start)) &&
        (slot.status === 'available' || slot.status === 'limited')
    );
    const lowCapacityTomorrow = tomorrowSlots.find(
      (slot) => (slot.remainingCapacity ?? slot.capacity) <= 5
    );

    if (lowCapacityTomorrow) {
      return {
        message: '🔥 Filling up fast for tomorrow',
        variant: 'warning',
      };
    }

    // Check overall limited availability (many slots at limited capacity)
    const limitedSlots = availabilityData.filter((slot) => slot.status === 'limited').length;
    const totalSlots = availabilityData.length;

    if (limitedSlots > 0 && limitedSlots / totalSlots > 0.3) {
      return {
        message: '📅 Limited availability this month',
        variant: 'info',
      };
    }
  }

  // Priority 2: Review-based urgency (only if actual reviews exist)
  if (hasActualReviews) {
    const reviewsCount = listing.reviewsCount || 0;
    const rating = listing.rating || 0;

    // High rated with many reviews
    if (rating >= 4.5 && reviewsCount >= 20) {
      return {
        message: `⭐ Top rated · ${reviewsCount} reviews`,
        variant: 'success',
      };
    }

    // Popular listing
    if (reviewsCount >= 10) {
      return {
        message: `🔥 Popular choice · ${reviewsCount} bookings`,
        variant: 'info',
      };
    }
  }

  return null;
}

export function FixedBookingPanel({
  listing,
  availabilityData,
  hasActualReviews = false,
  children,
}: FixedBookingPanelProps) {
  const t = useTranslations('listing');
  const [showBookingFlow, setShowBookingFlow] = useState(false);

  const basePrice = listing.pricing?.displayPrice || listing.pricing?.tndPrice || 0;
  const currency = listing.pricing?.displayCurrency || 'TND';

  // Get dynamic messages
  const cancellationMessage = getCancellationMessage(listing);
  const urgencyData = getUrgencyMessage(listing, availabilityData, hasActualReviews);

  // Urgency banner styling based on variant
  const getUrgencyStyles = (variant: 'warning' | 'success' | 'info') => {
    switch (variant) {
      case 'warning':
        return 'bg-red-50 border-red-200 text-red-900';
      case 'success':
        return 'bg-green-50 border-green-200 text-green-900';
      case 'info':
        return 'bg-blue-50 border-blue-200 text-blue-900';
      default:
        return 'bg-yellow-50 border-yellow-200 text-yellow-900';
    }
  };

  return (
    <div className="sticky top-24 w-full lg:w-[380px]">
      <div className="bg-[#fafaf9] rounded-2xl p-4 shadow-lg border border-neutral-200">
        <div>
          {/* Price Display */}
          <div className="mb-4">
            <PriceDisplay amount={basePrice} currency={currency} size="lg" showFrom />
          </div>

          {!showBookingFlow ? (
            // Initial state - Show "Check Availability" button + trust signals
            <div className="space-y-4">
              {/* Urgency Banner */}
              {urgencyData && (
                <div
                  className={`border rounded-lg px-3 py-2 ${getUrgencyStyles(urgencyData.variant)}`}
                >
                  <p className="text-sm font-medium">{urgencyData.message}</p>
                </div>
              )}

              <Button
                variant="primary"
                size="lg"
                className="w-full py-4 font-bold text-base"
                onClick={() => setShowBookingFlow(true)}
              >
                <Calendar className="h-5 w-5 mr-2" />
                {t('check_availability')}
              </Button>

              {/* Dynamic Trust Signals */}
              <div className="space-y-3 text-sm">
                <div className="flex items-center gap-2">
                  <Clock className="h-4 w-4 text-primary flex-shrink-0" />
                  <span className="text-neutral-800">Instant confirmation</span>
                </div>

                {cancellationMessage && (
                  <div className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary flex-shrink-0" />
                    <span className="text-neutral-800">{cancellationMessage}</span>
                  </div>
                )}

                {listing.minAdvanceBookingHours && listing.minAdvanceBookingHours > 0 && (
                  <div className="flex items-center gap-2">
                    <AlertCircle className="h-4 w-4 text-amber-600 flex-shrink-0" />
                    <span className="text-neutral-800">
                      Book at least {listing.minAdvanceBookingHours}h in advance
                    </span>
                  </div>
                )}

                <div className="flex items-center gap-2">
                  <Smartphone className="h-4 w-4 text-primary flex-shrink-0" />
                  <span className="text-neutral-800">Mobile ticket accepted</span>
                </div>

                {listing.maxGroupSize && (
                  <div className="flex items-center gap-2">
                    <Users className="h-4 w-4 text-primary flex-shrink-0" />
                    <span className="text-neutral-800">
                      Small group (max {listing.maxGroupSize})
                    </span>
                  </div>
                )}

                <div className="flex items-center gap-2">
                  <Shield className="h-4 w-4 text-primary flex-shrink-0" />
                  <span className="text-neutral-800">Secure payment</span>
                </div>
              </div>
            </div>
          ) : (
            // Booking flow state - Scrollable container
            <div className="space-y-4">
              {/* Close/Back button */}
              <div className="flex items-center justify-between">
                <button
                  onClick={() => setShowBookingFlow(false)}
                  className="text-sm text-neutral-600 hover:text-neutral-900 transition-colors font-medium"
                >
                  ← Back
                </button>
                <button
                  onClick={() => setShowBookingFlow(false)}
                  className="lg:hidden p-2 rounded-lg hover:bg-neutral-100 transition-colors"
                  aria-label="Close booking panel"
                >
                  <X className="h-5 w-5 text-neutral-600" />
                </button>
              </div>

              {/* Scrollable booking flow content */}
              <div className="max-h-[calc(100vh-16rem)] overflow-y-auto pr-2 -mr-2">{children}</div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
