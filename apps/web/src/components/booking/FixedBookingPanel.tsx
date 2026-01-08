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

// Helper function to get cancellation message key
function getCancellationMessageKey(listing: Listing): string | null {
  if (!listing.cancellationPolicy) return null;

  const policy = listing.cancellationPolicy;

  if (policy.type === 'flexible') {
    return 'cancellation_message.flexible';
  } else if (policy.type === 'moderate') {
    return 'cancellation_message.moderate';
  } else if (policy.type === 'strict') {
    return 'cancellation_message.strict';
  }

  return null;
}

// Helper function to get urgency message based on availability and reviews
function getUrgencyMessage(
  listing: Listing,
  availabilityData?: AvailabilitySlot[],
  hasActualReviews?: boolean
): { key: string; count?: number; variant: 'warning' | 'success' | 'info' } | null {
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
        key: 'urgency.spots_left_today',
        count: remaining,
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
        key: 'urgency.filling_fast_tomorrow',
        variant: 'warning',
      };
    }

    // Check overall limited availability (many slots at limited capacity)
    const limitedSlots = availabilityData.filter((slot) => slot.status === 'limited').length;
    const totalSlots = availabilityData.length;

    if (limitedSlots > 0 && limitedSlots / totalSlots > 0.3) {
      return {
        key: 'urgency.limited_month',
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
        key: 'urgency.top_rated',
        count: reviewsCount,
        variant: 'success',
      };
    }

    // Popular listing
    if (reviewsCount >= 10) {
      return {
        key: 'urgency.popular_choice',
        count: reviewsCount,
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
  const tCommon = useTranslations('common');
  const [showBookingFlow, setShowBookingFlow] = useState(false);

  const basePrice = listing.pricing?.displayPrice || listing.pricing?.tndPrice || 0;
  const currency = listing.pricing?.displayCurrency || 'TND';
  const isPriceNotSet = basePrice === 0;

  // Get dynamic message keys
  const cancellationMessageKey = getCancellationMessageKey(listing);
  const urgencyData = getUrgencyMessage(listing, availabilityData, hasActualReviews);

  // Urgency banner styling based on variant
  const getUrgencyStyles = (variant: 'warning' | 'success' | 'info') => {
    switch (variant) {
      case 'warning':
        return 'bg-error-light border-error/20 text-error-dark';
      case 'success':
        return 'bg-success-light border-success/20 text-success-dark';
      case 'info':
        return 'bg-success-light border-success/20 text-success-dark';
      default:
        return 'bg-warning-light border-warning/20 text-warning-dark';
    }
  };

  return (
    <div className="sticky top-24 w-full lg:w-[380px]">
      <div className="bg-[#fafaf9] rounded-2xl p-4 shadow-lg border border-neutral-200">
        <div>
          {/* Price Display */}
          <div className="mb-4" data-testid="listing-price">
            <PriceDisplay amount={basePrice} currency={currency} size="lg" showFrom />
            {isPriceNotSet && (
              <div className="mt-2 flex items-center gap-1.5 text-xs text-warning-dark">
                <AlertCircle className="h-3.5 w-3.5 flex-shrink-0" />
                <span>{t('pricing_not_configured')}</span>
              </div>
            )}
          </div>

          {!showBookingFlow ? (
            // Initial state - Show "Check Availability" button + trust signals
            <div className="space-y-4">
              {/* Urgency Banner */}
              {urgencyData && (
                <div
                  className={`border rounded-lg px-3 py-2 ${getUrgencyStyles(urgencyData.variant)}`}
                >
                  <p className="text-sm font-medium">
                    {urgencyData.count !== undefined
                      ? t(urgencyData.key, { count: urgencyData.count })
                      : t(urgencyData.key)}
                  </p>
                </div>
              )}

              <Button
                variant="primary"
                size="lg"
                className="w-full py-4 font-bold text-base"
                onClick={() => setShowBookingFlow(true)}
                data-testid="book-now-button"
              >
                <Calendar className="h-5 w-5 mr-2" />
                {t('check_availability')}
              </Button>

              {/* Dynamic Trust Signals */}
              <div className="space-y-3 text-sm">
                <div className="flex items-center gap-2">
                  <Clock className="h-4 w-4 text-primary flex-shrink-0" />
                  <span className="text-neutral-800">
                    {t('trust_signals.instant_confirmation')}
                  </span>
                </div>

                {cancellationMessageKey && (
                  <div className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary flex-shrink-0" />
                    <span className="text-neutral-800">{t(cancellationMessageKey)}</span>
                  </div>
                )}

                {(listing as any).minAdvanceBookingHours &&
                  (listing as any).minAdvanceBookingHours > 0 && (
                    <div className="flex items-center gap-2">
                      <AlertCircle className="h-4 w-4 text-warning flex-shrink-0" />
                      <span className="text-neutral-800">
                        {t('trust_signals.book_advance', {
                          hours: (listing as any).minAdvanceBookingHours,
                        })}
                      </span>
                    </div>
                  )}

                <div className="flex items-center gap-2">
                  <Smartphone className="h-4 w-4 text-primary flex-shrink-0" />
                  <span className="text-neutral-800">{t('trust_signals.mobile_ticket')}</span>
                </div>

                {listing.maxGroupSize && (
                  <div className="flex items-center gap-2">
                    <Users className="h-4 w-4 text-primary flex-shrink-0" />
                    <span className="text-neutral-800">
                      {t('trust_signals.small_group', { size: listing.maxGroupSize })}
                    </span>
                  </div>
                )}

                <div className="flex items-center gap-2">
                  <Shield className="h-4 w-4 text-primary flex-shrink-0" />
                  <span className="text-neutral-800">{t('trust_signals.secure_payment')}</span>
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
                  {tCommon('back_arrow')}
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
