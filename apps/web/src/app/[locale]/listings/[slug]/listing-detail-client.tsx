'use client';

import { useState, useMemo, useEffect } from 'react';
import Image from 'next/image';
import dynamic from 'next/dynamic';
import { useRouter } from '@/i18n/navigation';
import { useTranslations } from 'next-intl';
import { useQueryClient } from '@tanstack/react-query';
import { format, addMonths } from 'date-fns';
import { MainLayout } from '@/components/templates/MainLayout';
import { useAvailability, useCreateHold, useAddToCart } from '@/lib/api/hooks';
import { queryKeys } from '@/lib/api/query-keys';
import { Button } from '@djerba-fun/ui';
import { PersonTypeSelector } from '@/components/booking/PersonTypeSelector';
import { BookingStepIndicator, type BookingStep } from '@/components/booking/BookingStepIndicator';
import {
  PriceBreakdownTable,
  type PriceBreakdownItem,
} from '@/components/booking/PriceBreakdownTable';
import { SanitizedHtml } from '@/components/atoms/SanitizedHtml';

// Dynamic imports for heavy components to reduce initial bundle size
const AvailabilityCalendar = dynamic(
  () => import('@/components/availability/AvailabilityCalendar'),
  {
    loading: () => (
      <div className="h-96 w-full rounded-lg flex items-center justify-center bg-neutral-100 animate-pulse">
        <div className="text-neutral-500">Loading calendar...</div>
      </div>
    ),
    ssr: false,
  }
);

const ListingMap = dynamic(() => import('@/components/maps/ListingMap'), {
  loading: () => (
    <div className="h-96 w-full rounded-lg flex items-center justify-center bg-neutral-100 animate-pulse">
      <div className="text-neutral-500">Loading map...</div>
    </div>
  ),
  ssr: false,
});

const ItineraryTimeline = dynamic(() => import('@/components/itinerary/ItineraryTimeline'), {
  loading: () => (
    <div className="h-64 w-full rounded-lg flex items-center justify-center bg-neutral-100 animate-pulse">
      <div className="text-neutral-500">Loading itinerary...</div>
    </div>
  ),
  ssr: false,
});

const ElevationProfile = dynamic(() => import('@/components/itinerary/ElevationProfile'), {
  loading: () => (
    <div className="h-64 w-full rounded-lg flex items-center justify-center bg-neutral-100 animate-pulse">
      <div className="text-neutral-500">Loading elevation profile...</div>
    </div>
  ),
  ssr: false,
});

const AccommodationDateRangePicker = dynamic(
  () => import('@/components/availability/AccommodationDateRangePicker'),
  {
    loading: () => (
      <div className="h-96 w-full rounded-lg flex items-center justify-center bg-neutral-100 animate-pulse">
        <div className="text-neutral-500">Loading calendar...</div>
      </div>
    ),
    ssr: false,
  }
);

import NightlyPriceDisplay from '@/components/booking/NightlyPriceDisplay';

const BookingPanel = dynamic(
  () => import('@/components/booking/BookingPanel').then((mod) => ({ default: mod.BookingPanel })),
  {
    loading: () => (
      <div className="h-24 w-full rounded-lg flex items-center justify-center bg-neutral-100 animate-pulse">
        <div className="text-neutral-500">Loading...</div>
      </div>
    ),
    ssr: false,
  }
);

const FixedBookingPanel = dynamic(
  () =>
    import('@/components/booking/FixedBookingPanel').then((mod) => ({
      default: mod.FixedBookingPanel,
    })),
  {
    loading: () => (
      <div className="h-96 w-full rounded-lg flex items-center justify-center bg-neutral-50 animate-pulse">
        <div className="text-neutral-500">Loading booking panel...</div>
      </div>
    ),
    ssr: false,
  }
);
import { ImageLightbox } from '@/components/gallery/ImageLightbox';
import { FAQSection } from '@/components/listing/FAQSection';
import { SafetySection } from '@/components/listing/SafetySection';
import { AccessibilitySection } from '@/components/listing/AccessibilitySection';
import TimeSlotPicker from '@/components/availability/TimeSlotPicker';
import { AccommodationDetailsSection } from '@/components/listing/AccommodationDetailsSection';
import { NauticalDetailsSection } from '@/components/listing/NauticalDetailsSection';
import { CancellationPolicyCard } from '@/components/listing/CancellationPolicyCard';
import { ReviewsSection } from '@/components/listing/ReviewsSection';
import {
  MapPin,
  Clock,
  Users,
  Star,
  CheckCircle,
  XCircle,
  AlertCircle,
  ShoppingCart,
  Camera,
  Calendar,
  Package,
  ChevronDown,
  ChevronRight,
  Minus,
  Plus,
} from 'lucide-react';
import { resolveTranslation } from '@/lib/utils/translate';
import { getGuestSessionId } from '@/lib/utils/session';
import { normalizeMediaUrl } from '@/lib/utils/image';
import {
  getServiceTypeColors,
  getServiceTypeBadgeClasses,
  getServiceTypeDotClasses,
} from '@/lib/utils/serviceTypeColors';
import { cn } from '@/lib/utils/cn';
import type { AvailabilitySlot, Listing, PersonType } from '@djerba-fun/schemas';

// Parse API errors from hold creation
function parseHoldError(error: any, t: any): string {
  const response = error?.response?.data;

  // Check for structured capacity error
  if (response?.available !== undefined && response?.requested !== undefined) {
    return t('insufficient_capacity_detailed', {
      available: response.available,
      requested: response.requested,
    });
  }

  // Check for expired hold
  if (response?.message?.includes('expired') || response?.message?.includes('hold')) {
    return t('hold_expired_error');
  }

  // Fallback to generic message
  return response?.message || error?.message || t('generic_booking_error');
}

// Helper to safely parse price values (handles strings, numbers, null, undefined)
function parsePrice(val: unknown): number | null {
  if (val === null || val === undefined) return null;
  const num = typeof val === 'number' ? val : Number(val);
  return isNaN(num) ? null : num;
}

// Get person types from listing pricing or return defaults
function getPersonTypesFromListing(listing: Listing): PersonType[] {
  const pricing = listing.pricing || {};
  const personTypes = pricing.personTypes;

  // Get base price for fallback
  const basePrice = pricing.displayPrice || pricing.tndPrice || 0;
  const numericPrice = typeof basePrice === 'string' ? parseFloat(basePrice) : basePrice;

  if (personTypes && Array.isArray(personTypes) && personTypes.length > 0) {
    // Fill in missing 'price' field with displayPrice/tndPrice/eurPrice
    // Use parsePrice() to handle string values from API, and ?? for null fallthrough
    return personTypes.map((pt: any) => ({
      ...pt,
      price:
        parsePrice(pt.price) ??
        parsePrice(pt.displayPrice) ??
        parsePrice(pt.tndPrice) ??
        parsePrice(pt.eurPrice) ??
        numericPrice,
    }));
  }

  // Return defaults based on display price (or fallback to TND price)

  return [
    {
      key: 'adult',
      label: { en: 'Adult', fr: 'Adulte' },
      price: numericPrice,
      minAge: 18,
      maxAge: null,
      minQuantity: 1,
      maxQuantity: null,
    },
    {
      key: 'child',
      label: { en: 'Child (4-17)', fr: 'Enfant (4-17)' },
      price: Math.round(numericPrice * 0.5),
      minAge: 4,
      maxAge: 17,
      minQuantity: 0,
      maxQuantity: null,
    },
    {
      key: 'infant',
      label: { en: 'Infant (0-3)', fr: 'Bébé (0-3)' },
      price: 0,
      minAge: 0,
      maxAge: 3,
      minQuantity: 0,
      maxQuantity: null,
    },
  ];
}

// Calculate total from person type breakdown.
// When `slot` is provided and carries effectivePrices for the active currency,
// prefer the slot-effective per-type price over the listing default — same
// override-aware precedence as the PriceBreakdownTable line items below, so
// the booking-panel grand total matches the per-line subtotals when a slot
// has price_overrides configured.
function calculateTotalFromBreakdown(
  personTypes: PersonType[],
  breakdown: Record<string, number>,
  slot?: AvailabilitySlot,
  currency?: string
): { totalGuests: number; totalPrice: number } {
  let totalGuests = 0;
  let totalPrice = 0;

  const effectiveForCurrency =
    slot && (currency === 'TND' || currency === 'EUR')
      ? slot.effectivePrices?.[currency]
      : undefined;

  for (const type of personTypes) {
    const quantity = breakdown[type.key] || 0;
    totalGuests += quantity;
    const slotEffective = effectiveForCurrency?.[type.key];
    const unitPrice =
      typeof slotEffective === 'number' && !Number.isNaN(slotEffective)
        ? slotEffective
        : (type.price ?? 0);
    totalPrice += unitPrice * quantity;
  }

  return { totalGuests, totalPrice };
}

// Booking flow content component - extracted for reuse in both desktop and mobile
interface BookingFlowContentProps {
  isLoadingAvailability: boolean;
  availabilityData: AvailabilitySlot[] | undefined;
  selectedDate: Date | undefined;
  selectedSlot: AvailabilitySlot | undefined;
  slotsForSelectedDate: AvailabilitySlot[];
  personTypes: PersonType[];
  personTypeBreakdown: Record<string, number>;
  maxCapacity: number;
  listing: Listing;
  createHoldMutation: ReturnType<typeof useCreateHold>;
  onDateSelect: (date: Date) => void;
  onSlotSelect: (slot: AvailabilitySlot) => void;
  onPersonTypeChange: (breakdown: Record<string, number>) => void;
  onCreateHold: () => void;
  onAddToCart: () => void;
  isAddingToCart: boolean;
  onClose: () => void;
  locale: string;
  t: ReturnType<typeof useTranslations>;
  tAvail: ReturnType<typeof useTranslations>;
  tBooking: ReturnType<typeof useTranslations>;
  tCommon: ReturnType<typeof useTranslations>;
  tCart: ReturnType<typeof useTranslations>;
  // Extras selection
  selectedExtras: { id: string; quantity: number }[];
  extrasExpanded: boolean;
  onExtrasExpandedChange: (expanded: boolean) => void;
  onExtraIncrement: (extraId: string, maxQty?: number | null) => void;
  onExtraDecrement: (extraId: string) => void;
  getExtraQuantity: (extraId: string) => number;
}

function BookingFlowContent({
  isLoadingAvailability,
  availabilityData,
  selectedDate,
  selectedSlot,
  slotsForSelectedDate,
  personTypes,
  personTypeBreakdown,
  maxCapacity,
  listing,
  createHoldMutation,
  onDateSelect,
  onSlotSelect,
  onPersonTypeChange,
  onCreateHold,
  onAddToCart,
  isAddingToCart,
  onClose,
  locale,
  t,
  tAvail,
  tBooking,
  tCommon,
  tCart,
  selectedExtras,
  extrasExpanded,
  onExtrasExpandedChange,
  onExtraIncrement,
  onExtraDecrement,
  getExtraQuantity,
}: BookingFlowContentProps) {
  const [wizardStep, setWizardStep] = useState<1 | 2 | 3>(1);

  // Calculate totals from breakdown — pass the selected slot + currency so
  // per-slot price_overrides are applied to the grand total. Without this,
  // the per-line items (which DO read selectedSlot.effectivePrices) would
  // diverge from the displayed grand total.
  const bookingPanelCurrency = selectedSlot?.currency || listing.pricing?.displayCurrency || 'TND';
  const { totalGuests, totalPrice } = calculateTotalFromBreakdown(
    personTypes,
    personTypeBreakdown,
    selectedSlot,
    bookingPanelCurrency
  );
  const canProceed = totalGuests > 0;

  // Determine current step and completed steps for indicator
  const getCurrentStep = (): BookingStep => {
    if (wizardStep === 1) return 'date';
    if (wizardStep === 2) return 'time';
    return 'guests';
  };

  const getCompletedSteps = (): BookingStep[] => {
    const completed: BookingStep[] = [];
    if (selectedDate) completed.push('date');
    if (selectedSlot) completed.push('time');
    if (totalGuests > 0 && selectedSlot) completed.push('guests');
    return completed;
  };

  // Handle step transitions
  const handleDateSelect = (date: Date) => {
    onDateSelect(date);
    setWizardStep(2); // Auto-advance to time slots
  };

  const handleSlotSelect = (slot: AvailabilitySlot) => {
    onSlotSelect(slot);
    setWizardStep(3); // Auto-advance to participants
  };

  const handleBack = () => {
    if (wizardStep > 1) {
      setWizardStep((prev) => (prev - 1) as 1 | 2 | 3);
    }
  };

  const currentStep = getCurrentStep();
  const completedSteps = getCompletedSteps();

  return (
    <div className="space-y-4">
      {/* Step Progress Indicator */}
      <BookingStepIndicator currentStep={currentStep} completedSteps={completedSteps} />

      {/* Back Button (show on steps 2 and 3) */}
      {wizardStep > 1 && (
        <button
          onClick={handleBack}
          className="flex items-center gap-2 text-sm text-neutral-600 hover:text-heading transition-colors"
        >
          <span>←</span>
          <span>Back</span>
        </button>
      )}

      {/* STEP 1: Calendar Only */}
      {wizardStep === 1 && (
        <div>
          <h3 className="font-semibold text-heading mb-4">{tAvail('select_date')}</h3>
          {isLoadingAvailability ? (
            <p className="text-center text-neutral-500 py-8">{tCommon('loading')}</p>
          ) : (
            <AvailabilityCalendar
              slots={availabilityData || []}
              onDateSelect={handleDateSelect}
              selectedDate={selectedDate}
            />
          )}
        </div>
      )}

      {/* STEP 2: Time Slots Only — single source of truth via shared TimeSlotPicker */}
      {wizardStep === 2 && (
        <TimeSlotPicker
          slots={slotsForSelectedDate}
          selectedSlot={selectedSlot}
          onSlotSelect={handleSlotSelect}
        />
      )}

      {/* STEP 3: Participants Only */}
      {wizardStep === 3 && selectedSlot && (
        <div>
          <h3 className="font-semibold text-heading mb-4">{tBooking('travelers')}</h3>

          {/* Slot capacity indicator */}
          <div className="mb-4 p-3 bg-gray-50 border border-gray-200 rounded-lg">
            <div className="flex items-center justify-between text-sm">
              <span className="text-gray-600">{tBooking('time_slot_capacity')}</span>
              <span
                className={`font-semibold ${
                  selectedSlot.status === 'limited' ? 'text-warning-dark' : 'text-success-dark'
                }`}
              >
                {selectedSlot.remainingCapacity} / {selectedSlot.capacity} {tBooking('available')}
              </span>
            </div>
          </div>

          <PersonTypeSelector
            personTypes={personTypes}
            value={personTypeBreakdown}
            onChange={onPersonTypeChange}
            currency={listing.pricing?.displayCurrency || 'EUR'}
            maxCapacity={maxCapacity}
            locale={locale}
          />

          {/* Collapsible Extras Section */}
          {listing.extras && listing.extras.length > 0 && (
            <div className="mt-6 pt-4 border-t border-neutral-200" data-testid="extras-selection">
              <button
                onClick={() => onExtrasExpandedChange(!extrasExpanded)}
                className="flex items-center justify-between w-full text-left py-2 cursor-pointer"
              >
                <span className="font-semibold text-heading flex items-center gap-2">
                  <Package
                    className={cn('h-5 w-5', getServiceTypeColors(listing.serviceType).accent)}
                  />
                  {tBooking('add_extras')}
                  <span className="text-sm font-normal text-neutral-500">
                    ({listing.extras.length} {tBooking('available')})
                  </span>
                </span>
                <ChevronDown
                  className={`h-5 w-5 text-neutral-500 transition-transform ${extrasExpanded ? 'rotate-180' : ''}`}
                />
              </button>

              {extrasExpanded && (
                <div className="mt-4 space-y-3">
                  {listing.extras.map((extra: any) => {
                    const qty = getExtraQuantity(extra.id);
                    const currency =
                      selectedSlot?.currency || listing.pricing?.displayCurrency || 'TND';
                    const price = currency === 'TND' ? extra.priceTnd : extra.priceEur;
                    const pricingLabel =
                      extra.pricingType === 'per_person'
                        ? tBooking('per_person')
                        : extra.pricingType === 'per_booking'
                          ? tBooking('per_booking')
                          : tBooking('per_unit');

                    return (
                      <div
                        key={extra.id}
                        className="flex items-center justify-between p-3 bg-neutral-50 rounded-lg"
                        data-testid={`extra-item-${extra.id}`}
                      >
                        <div className="flex-1 min-w-0">
                          <div className="font-medium text-heading truncate">{extra.name}</div>
                          <div className="text-sm text-neutral-500">
                            {price?.toFixed(2)} {currency} {pricingLabel}
                          </div>
                        </div>
                        <div className="flex items-center gap-2 ml-4">
                          <button
                            onClick={() => onExtraDecrement(extra.id)}
                            disabled={qty === 0}
                            className="w-8 h-8 rounded-full border border-neutral-300 flex items-center justify-center disabled:opacity-40 hover:bg-neutral-100 transition-colors cursor-pointer disabled:cursor-not-allowed"
                          >
                            <Minus className="h-4 w-4" />
                          </button>
                          <span className="w-8 text-center font-medium">{qty}</span>
                          <button
                            onClick={() => onExtraIncrement(extra.id, extra.maxQuantity)}
                            className="w-8 h-8 rounded-full border border-neutral-300 flex items-center justify-center hover:bg-neutral-100 transition-colors cursor-pointer"
                          >
                            <Plus className="h-4 w-4" />
                          </button>
                        </div>
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          )}

          {/* Price Breakdown Table */}
          {totalGuests > 0 && (
            <div className="mt-6 pt-4 border-t border-neutral-200">
              <PriceBreakdownTable
                items={(() => {
                  const items: PriceBreakdownItem[] = [];
                  const currency =
                    selectedSlot?.currency || listing.pricing?.displayCurrency || 'TND';

                  // Add person types with qty > 0
                  for (const [key, qty] of Object.entries(personTypeBreakdown)) {
                    if (qty > 0) {
                      const pt = personTypes.find((p) => p.key === key);
                      if (pt) {
                        const label =
                          typeof pt.label === 'object'
                            ? (pt.label as any)[locale] || (pt.label as any).en || key
                            : pt.label || key;
                        // Use parsePrice for safety - handles string values from API
                        const slotBasePrice =
                          parsePrice(selectedSlot?.displayPrice) ??
                          parsePrice(selectedSlot?.basePrice) ??
                          0;
                        // Slot-effective per-person-type price (override-aware).
                        // The API resolves listing.pricing[key] vs slot.priceOverrides[key]
                        // server-side — the frontend just renders whichever one
                        // effectivePrices[currency][key] holds. Falls back to the
                        // listing's per-type price, then the slot's headline price,
                        // for legacy slots that lack the effectivePrices field.
                        const slotEffective =
                          currency === 'TND' || currency === 'EUR'
                            ? parsePrice(selectedSlot?.effectivePrices?.[currency]?.[key])
                            : null;
                        const unitPrice =
                          slotEffective ??
                          parsePrice(pt.price) ??
                          parsePrice(pt.displayPrice) ??
                          slotBasePrice;
                        items.push({
                          type: 'person',
                          key,
                          label,
                          quantity: qty,
                          unitPrice,
                          subtotal: unitPrice * qty,
                        });
                      }
                    }
                  }

                  // Add extras with qty > 0
                  for (const extra of selectedExtras) {
                    if (extra.quantity > 0) {
                      const listingExtra = listing.extras?.find((e: any) => e.id === extra.id);
                      if (listingExtra) {
                        const price =
                          currency === 'TND' ? listingExtra.priceTnd : listingExtra.priceEur;
                        items.push({
                          type: 'extra',
                          key: extra.id,
                          label: listingExtra.name || 'Extra',
                          quantity: extra.quantity,
                          unitPrice: price || 0,
                          subtotal: (price || 0) * extra.quantity,
                        });
                      }
                    }
                  }

                  return items;
                })()}
                currency={selectedSlot?.currency || listing.pricing?.displayCurrency || 'TND'}
                total={
                  (typeof totalPrice === 'number' && !isNaN(totalPrice) ? totalPrice : 0) +
                  selectedExtras.reduce((sum, extra) => {
                    const listingExtra = listing.extras?.find((e: any) => e.id === extra.id);
                    const currency =
                      selectedSlot?.currency || listing.pricing?.displayCurrency || 'TND';
                    const price =
                      currency === 'TND' ? listingExtra?.priceTnd : listingExtra?.priceEur;
                    return (
                      sum +
                      (typeof price === 'number' && !isNaN(price) ? price : 0) * extra.quantity
                    );
                  }, 0)
                }
                compact={true}
              />
            </div>
          )}

          {/* Book Buttons */}
          <div className="pt-6 space-y-3">
            <Button
              variant="primary"
              size="lg"
              className="w-full"
              onClick={onCreateHold}
              disabled={createHoldMutation.isPending || isAddingToCart || !canProceed}
            >
              {createHoldMutation.isPending ? tCommon('loading') : tBooking('continue')}
            </Button>
            <Button
              variant="outline"
              size="lg"
              className="w-full"
              onClick={onAddToCart}
              disabled={createHoldMutation.isPending || isAddingToCart || !canProceed}
            >
              <ShoppingCart className="h-5 w-5 mr-2" />
              {isAddingToCart ? tCommon('loading') : tCart('add_to_cart')}
            </Button>
            {createHoldMutation.isError && (
              <div className="bg-error-light border border-error rounded-lg p-4 mt-3">
                <div className="flex items-start gap-3">
                  <AlertCircle className="h-5 w-5 text-error-dark flex-shrink-0 mt-0.5" />
                  <div className="flex-1">
                    <p className="text-sm font-medium text-error-dark mb-1">
                      {tBooking('booking_failed')}
                    </p>
                    <p className="text-sm text-error-dark">
                      {parseHoldError(createHoldMutation.error, tBooking)}
                    </p>
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}

// Accommodation Booking Flow Content - date range selection with nightly pricing
interface AccommodationBookingFlowContentProps {
  isLoadingAvailability: boolean;
  availabilityData: AvailabilitySlot[] | undefined;
  listing: Listing;
  createHoldMutation: ReturnType<typeof useCreateHold>;
  onCreateHold: (checkIn: Date, checkOut: Date, guests: number) => void;
  onAddToCart: (checkIn: Date, checkOut: Date, guests: number) => void;
  isAddingToCart: boolean;
  locale: string;
  tAccommodation: ReturnType<typeof useTranslations>;
  tCommon: ReturnType<typeof useTranslations>;
  tBooking: ReturnType<typeof useTranslations>;
  tCart: ReturnType<typeof useTranslations>;
  // Extras selection
  selectedExtras: { id: string; quantity: number }[];
  extrasExpanded: boolean;
  onExtrasExpandedChange: (expanded: boolean) => void;
  onExtraIncrement: (extraId: string, maxQty?: number | null) => void;
  onExtraDecrement: (extraId: string) => void;
  getExtraQuantity: (extraId: string) => number;
}

function AccommodationBookingFlowContent({
  isLoadingAvailability,
  availabilityData,
  listing,
  createHoldMutation,
  onCreateHold,
  onAddToCart,
  isAddingToCart,
  locale,
  tAccommodation,
  tCommon,
  tBooking,
  tCart,
  selectedExtras,
  extrasExpanded,
  onExtrasExpandedChange,
  onExtraIncrement,
  onExtraDecrement,
  getExtraQuantity,
}: AccommodationBookingFlowContentProps) {
  const [dateRange, setDateRange] = useState<{
    checkIn: Date;
    checkOut: Date;
    nights: number;
  } | null>(null);
  const [guestCount, setGuestCount] = useState(2);

  const pricing = listing.pricing || {};
  const nightlyPrice = pricing.nightlyDisplayPrice || pricing.displayPrice || 0;
  const currency = pricing.displayCurrency || 'EUR';
  const minimumNights = pricing.minimumNights || 1;
  const maximumNights = pricing.maximumNights || null;
  // Type-safe accommodation access - this component is only used for accommodation listings
  const accommodation = 'accommodation' in listing ? listing.accommodation : null;
  const maxGuests = accommodation?.maxGuests || listing.maxGroupSize || 10;
  const checkInTime = accommodation?.checkInTime || '15:00';

  const canProceed = dateRange && dateRange.nights >= minimumNights && guestCount > 0;

  // Calculate extras total
  const extrasTotal = selectedExtras.reduce((sum, extra) => {
    const listingExtra = listing.extras?.find((e: any) => e.id === extra.id);
    const price = currency === 'TND' ? listingExtra?.priceTnd : listingExtra?.priceEur;
    return sum + (price || 0) * extra.quantity;
  }, 0);

  const handleDateRangeChange = (
    selection: { checkIn: Date; checkOut: Date; nights: number } | null
  ) => {
    setDateRange(selection);
  };

  const handleCreateHold = () => {
    console.log('[AccommodationBooking] Local handleCreateHold called', {
      hasDateRange: !!dateRange,
      dateRange,
      guestCount,
    });
    if (dateRange) {
      onCreateHold(dateRange.checkIn, dateRange.checkOut, guestCount);
    } else {
      console.log('[AccommodationBooking] SKIPPED: dateRange is null/undefined');
    }
  };

  const handleAddToCart = () => {
    if (dateRange) {
      onAddToCart(dateRange.checkIn, dateRange.checkOut, guestCount);
    }
  };

  return (
    <div className="space-y-6">
      {/* Date Range Picker */}
      <div>
        <h3 className="font-semibold text-heading mb-4">{tAccommodation('select_dates')}</h3>
        {isLoadingAvailability ? (
          <p className="text-center text-neutral-500 py-8">{tCommon('loading')}</p>
        ) : (
          <AccommodationDateRangePicker
            slots={availabilityData || []}
            minimumNights={minimumNights}
            maximumNights={maximumNights}
            nightlyPrice={nightlyPrice}
            currency={currency}
            checkInTime={checkInTime}
            onSelectionChange={handleDateRangeChange}
            serviceTypeColor="amber"
          />
        )}
      </div>

      {/* Guest Count Selector */}
      {dateRange && (
        <div className="rounded-lg border border-neutral-200 bg-white p-4">
          <h4 className="font-medium mb-3">{tAccommodation('guests')}</h4>
          <div className="flex items-center justify-between">
            <span className="text-sm text-neutral-600">
              {tAccommodation('guests_count', { max: maxGuests })}
            </span>
            <div className="flex items-center gap-3">
              <button
                onClick={() => setGuestCount((prev) => Math.max(1, prev - 1))}
                disabled={guestCount <= 1}
                className="w-8 h-8 rounded-full border border-neutral-300 flex items-center justify-center disabled:opacity-40 hover:bg-neutral-100 transition-colors cursor-pointer disabled:cursor-not-allowed"
              >
                <Minus className="h-4 w-4" />
              </button>
              <span className="w-8 text-center font-semibold">{guestCount}</span>
              <button
                onClick={() => setGuestCount((prev) => Math.min(maxGuests, prev + 1))}
                disabled={guestCount >= maxGuests}
                className="w-8 h-8 rounded-full border border-neutral-300 flex items-center justify-center disabled:opacity-40 hover:bg-neutral-100 transition-colors cursor-pointer disabled:cursor-not-allowed"
              >
                <Plus className="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Collapsible Extras Section */}
      {dateRange && listing.extras && listing.extras.length > 0 && (
        <div
          className="rounded-lg border border-neutral-200 bg-white p-4"
          data-testid="extras-selection"
        >
          <button
            onClick={() => onExtrasExpandedChange(!extrasExpanded)}
            className="flex items-center justify-between w-full text-left cursor-pointer"
          >
            <span className="font-semibold text-heading flex items-center gap-2">
              <Package className="h-5 w-5 text-amber-600" />
              {tBooking('add_extras')}
              <span className="text-sm font-normal text-neutral-500">
                ({listing.extras.length} {tBooking('available')})
              </span>
            </span>
            <ChevronDown
              className={`h-5 w-5 text-neutral-500 transition-transform ${extrasExpanded ? 'rotate-180' : ''}`}
            />
          </button>

          {extrasExpanded && (
            <div className="mt-4 space-y-3">
              {listing.extras.map((extra: any) => {
                const qty = getExtraQuantity(extra.id);
                const price = currency === 'TND' ? extra.priceTnd : extra.priceEur;
                const pricingLabel =
                  extra.pricingType === 'per_person'
                    ? tBooking('per_person')
                    : extra.pricingType === 'per_booking'
                      ? tBooking('per_booking')
                      : tBooking('per_unit');

                return (
                  <div
                    key={extra.id}
                    className="flex items-center justify-between p-3 bg-neutral-50 rounded-lg"
                    data-testid={`extra-item-${extra.id}`}
                  >
                    <div className="flex-1 min-w-0">
                      <div className="font-medium text-heading truncate">{extra.name}</div>
                      <div className="text-sm text-neutral-500">
                        {price?.toFixed(2)} {currency} {pricingLabel}
                      </div>
                    </div>
                    <div className="flex items-center gap-2 ml-4">
                      <button
                        onClick={() => onExtraDecrement(extra.id)}
                        disabled={qty === 0}
                        className="w-8 h-8 rounded-full border border-neutral-300 flex items-center justify-center disabled:opacity-40 hover:bg-neutral-100 transition-colors cursor-pointer disabled:cursor-not-allowed"
                      >
                        <Minus className="h-4 w-4" />
                      </button>
                      <span className="w-8 text-center font-medium">{qty}</span>
                      <button
                        onClick={() => onExtraIncrement(extra.id, extra.maxQuantity)}
                        className="w-8 h-8 rounded-full border border-neutral-300 flex items-center justify-center hover:bg-neutral-100 transition-colors cursor-pointer"
                      >
                        <Plus className="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      )}

      {/* Price Display */}
      {dateRange && (
        <NightlyPriceDisplay
          nights={dateRange.nights}
          nightlyPrice={nightlyPrice}
          currency={currency}
          guests={guestCount}
          maxGuests={maxGuests}
          selectedExtras={selectedExtras
            .filter((e) => e.quantity > 0)
            .map((e) => {
              const listingExtra = listing.extras?.find((le: any) => le.id === e.id);
              const price = currency === 'TND' ? listingExtra?.priceTnd : listingExtra?.priceEur;
              return {
                id: e.id,
                name: listingExtra?.name || 'Extra',
                price: price || 0,
                quantity: e.quantity,
              };
            })}
        />
      )}

      {/* Book Buttons */}
      {dateRange && (
        <div className="space-y-3">
          <Button
            variant="primary"
            size="lg"
            className="w-full"
            onClick={() => {
              console.log('[AccommodationBooking] Continue button clicked');
              handleCreateHold();
            }}
            disabled={createHoldMutation.isPending || isAddingToCart || !canProceed}
          >
            {createHoldMutation.isPending ? tCommon('loading') : tBooking('continue')}
          </Button>
          <Button
            variant="outline"
            size="lg"
            className="w-full"
            onClick={handleAddToCart}
            disabled={createHoldMutation.isPending || isAddingToCart || !canProceed}
          >
            <ShoppingCart className="h-5 w-5 mr-2" />
            {isAddingToCart ? tCommon('loading') : tCart('add_to_cart')}
          </Button>
          {createHoldMutation.isError && (
            <div className="bg-error-light border border-error rounded-lg p-4 mt-3">
              <div className="flex items-start gap-3">
                <AlertCircle className="h-5 w-5 text-error-dark flex-shrink-0 mt-0.5" />
                <div className="flex-1">
                  <p className="text-sm font-medium text-error-dark mb-1">
                    {tBooking('booking_failed')}
                  </p>
                  <p className="text-sm text-error-dark">
                    {parseHoldError(createHoldMutation.error, tBooking)}
                  </p>
                </div>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

// Route & Itinerary Tabs Component
interface RouteItineraryTabsProps {
  itinerary: any[];
  center: [number, number];
  title: string;
  imageUrl?: string;
  locale: string;
  isAccommodation?: boolean;
  skipRouting?: boolean;
  routingProfile?: 'foot' | 'driving' | 'cycling';
  serviceType: 'tour' | 'nautical' | 'accommodation' | 'event';
  mapDisplayType?: 'markers' | 'circle';
}

function RouteItineraryTabs({
  itinerary,
  center,
  title,
  imageUrl,
  locale,
  isAccommodation,
  skipRouting,
  routingProfile,
  serviceType,
  mapDisplayType = 'markers',
}: RouteItineraryTabsProps) {
  const [activeTab, setActiveTab] = useState<'map' | 'itinerary'>('map');
  const t = useTranslations('listing');
  const colors = getServiceTypeColors(serviceType);

  // Get border color class for active tab
  const getActiveTabClasses = () => {
    const borderColors: Record<string, string> = {
      tour: 'border-emerald-600',
      nautical: 'border-navy-600',
      accommodation: 'border-orange-600',
      event: 'border-gold-600',
    };
    return `${colors.accent} border-b-2 ${borderColors[serviceType] || borderColors.tour}`;
  };

  return (
    <div className="space-y-4">
      {/* Tab Buttons */}
      <div className="flex gap-2 border-b border-neutral-200">
        <button
          onClick={() => setActiveTab('map')}
          className={cn(
            'px-6 py-3 font-semibold transition-colors relative',
            activeTab === 'map' ? getActiveTabClasses() : 'text-neutral-600 hover:text-neutral-900'
          )}
        >
          {t('trail_map')}
        </button>
        <button
          onClick={() => setActiveTab('itinerary')}
          className={cn(
            'px-6 py-3 font-semibold transition-colors relative',
            activeTab === 'itinerary'
              ? getActiveTabClasses()
              : 'text-neutral-600 hover:text-neutral-900'
          )}
        >
          {t('itinerary')}
        </button>
      </div>

      {/* Tab Content */}
      <div className="mt-6">
        {activeTab === 'map' && (
          <div className="h-[600px] rounded-lg overflow-hidden border border-neutral-200 relative z-0">
            <ListingMap
              center={center}
              title={title}
              imageUrl={imageUrl}
              itinerary={itinerary}
              isAccommodation={isAccommodation}
              skipRouting={skipRouting}
              routingProfile={routingProfile}
              locale={locale}
              className="h-full w-full"
              mapDisplayType={mapDisplayType}
            />
          </div>
        )}

        {activeTab === 'itinerary' && (
          <div className="bg-white rounded-lg border border-neutral-200 p-6">
            <ItineraryTimeline
              stops={itinerary}
              locale={locale}
              isAccommodation={isAccommodation}
            />
          </div>
        )}
      </div>
    </div>
  );
}

interface ListingDetailClientProps {
  listing: Listing;
  locale: string;
  slug: string;
}

export default function ListingDetailClient({ listing, locale, slug }: ListingDetailClientProps) {
  const router = useRouter();
  const queryClient = useQueryClient();
  const t = useTranslations('listing');
  const tCommon = useTranslations('common');
  const tAvail = useTranslations('availability');
  const tBooking = useTranslations('booking');
  const tCart = useTranslations('cart');
  const tAccommodation = useTranslations('accommodation');

  // Check if this is an accommodation listing
  const isAccommodationListing = listing.serviceType === 'accommodation';

  // Booking flow state
  const [showBookingFlow, setShowBookingFlow] = useState(false);
  const [selectedDate, setSelectedDate] = useState<Date | undefined>();
  const [selectedSlot, setSelectedSlot] = useState<AvailabilitySlot | undefined>();
  const [personTypeBreakdown, setPersonTypeBreakdown] = useState<Record<string, number>>({
    adult: 1,
  });

  // Extras selection state
  const [selectedExtras, setSelectedExtras] = useState<{ id: string; quantity: number }[]>([]);
  const [extrasExpanded, setExtrasExpanded] = useState(false);

  // Lightbox state
  const [lightboxOpen, setLightboxOpen] = useState(false);
  const [lightboxIndex, setLightboxIndex] = useState(0);

  // Get person types from listing, then overlay the selected slot's
  // effectivePrices when one is picked. The Voyageurs quantity-selector
  // labels read pt.price directly, so without this overlay they kept showing
  // listing defaults (€38 adult) while the breakdown below them showed the
  // slot-overridden price (€48 adult). Centralising slot-awareness here
  // means every consumer of personTypes (selector labels, breakdown items,
  // grand-total helper) tells the customer the same number — what they're
  // actually charged. Listing pricing remains the fallback when no slot is
  // selected, the slot has no override for a given key, or the API hasn't
  // surfaced effectivePrices yet (legacy slots).
  const personTypes = useMemo(() => {
    const fromListing = getPersonTypesFromListing(listing);

    if (!selectedSlot) {
      return fromListing;
    }

    const currency = selectedSlot.currency || listing.pricing?.displayCurrency;
    if (currency !== 'TND' && currency !== 'EUR') {
      return fromListing;
    }

    const effective = selectedSlot.effectivePrices?.[currency];
    if (!effective) {
      return fromListing;
    }

    return fromListing.map((pt) => {
      const slotPrice = effective[pt.key];
      if (typeof slotPrice !== 'number' || Number.isNaN(slotPrice)) {
        return pt;
      }
      return { ...pt, price: slotPrice };
    });
  }, [listing, selectedSlot]);

  // Get availability for the next 6 months (always fetch since booking panel is visible from start)
  const startDate = format(new Date(), 'yyyy-MM-dd');
  const endDate = format(addMonths(new Date(), 6), 'yyyy-MM-dd');
  const { data: availabilityData, isLoading: isLoadingAvailability } = useAvailability(
    slug,
    startDate,
    endDate,
    true // Always fetch availability since booking panel is visible from the start
  );

  const createHoldMutation = useCreateHold(slug);
  const addToCartMutation = useAddToCart();

  // State for cart success message
  const [showCartSuccess, setShowCartSuccess] = useState(false);

  // State for booking error message
  const [bookingError, setBookingError] = useState<string | null>(null);

  // Get time slots for selected date
  const slotsForSelectedDate = useMemo(() => {
    if (!selectedDate || !availabilityData) return [];
    const dateStr = format(selectedDate, 'yyyy-MM-dd');
    return availabilityData.filter(
      (slot) =>
        slot.start.startsWith(dateStr) && (slot.status === 'available' || slot.status === 'limited')
    );
  }, [selectedDate, availabilityData]);

  const handleDateSelect = (date: Date) => {
    setSelectedDate(date);
    setSelectedSlot(undefined);
  };

  const handleSlotSelect = (slot: AvailabilitySlot) => {
    setSelectedSlot(slot);
  };

  // Extras helper functions
  const getExtraQuantity = (extraId: string) => {
    return selectedExtras.find((e) => e.id === extraId)?.quantity || 0;
  };

  const incrementExtra = (extraId: string, maxQty?: number | null) => {
    setSelectedExtras((prev) => {
      const existing = prev.find((e) => e.id === extraId);
      if (existing) {
        const newQty = existing.quantity + 1;
        if (maxQty && newQty > maxQty) return prev;
        return prev.map((e) => (e.id === extraId ? { ...e, quantity: newQty } : e));
      }
      return [...prev, { id: extraId, quantity: 1 }];
    });
  };

  const decrementExtra = (extraId: string) => {
    setSelectedExtras((prev) => {
      const existing = prev.find((e) => e.id === extraId);
      if (existing && existing.quantity > 1) {
        return prev.map((e) => (e.id === extraId ? { ...e, quantity: e.quantity - 1 } : e));
      }
      return prev.filter((e) => e.id !== extraId);
    });
  };

  const handleCreateHold = async () => {
    if (!selectedSlot) return;

    // Filter out zero values from breakdown
    const filteredBreakdown = Object.fromEntries(
      Object.entries(personTypeBreakdown).filter(([, qty]) => qty > 0)
    );

    // Validate breakdown has at least one person
    const totalGuests = Object.values(filteredBreakdown).reduce((sum, qty) => sum + qty, 0);
    if (totalGuests === 0) {
      console.error('No guests selected');
      return;
    }

    // Log for debugging
    console.log('[Booking] Creating hold with breakdown:', {
      filteredBreakdown,
      totalGuests,
      slotCapacity: selectedSlot.remainingCapacity,
    });

    // Track if we should navigate after the operation
    let shouldNavigate = false;

    try {
      const sessionId = getGuestSessionId();
      const response = await createHoldMutation.mutateAsync({
        slotId: String(selectedSlot.id),
        person_types: filteredBreakdown,
        session_id: sessionId,
        extras: selectedExtras,
      });
      const holdId = response.data.id;
      console.log('[Booking] Hold created:', holdId);

      // Persist extras to sessionStorage for checkout (skip extras step if pre-selected)
      if (selectedExtras.length > 0) {
        sessionStorage.setItem(`checkout-extras-${holdId}`, JSON.stringify(selectedExtras));
      }

      // Add to cart (preserves existing cart items)
      console.log('[Booking] Adding to cart...');
      await addToCartMutation.mutateAsync(holdId);
      console.log('[Booking] Added to cart');

      // Navigate to checkout using window.location for guaranteed navigation
      // Note: refetchQueries can hang indefinitely - the mutation's onSuccess already invalidates cart
      window.location.href = locale === 'fr' ? '/cart/checkout' : `/${locale}/cart/checkout`;
    } catch (err) {
      console.error('[Booking] Error:', err);
    }
  };

  const handleAddToCart = async () => {
    if (!selectedSlot) return;

    // Filter out zero values from breakdown
    const filteredBreakdown = Object.fromEntries(
      Object.entries(personTypeBreakdown).filter(([, qty]) => qty > 0)
    );

    try {
      const sessionId = getGuestSessionId();
      // First create a hold with selected extras
      const holdResponse = await createHoldMutation.mutateAsync({
        slotId: String(selectedSlot.id),
        person_types: filteredBreakdown,
        session_id: sessionId,
        extras: selectedExtras,
      });
      const holdId = holdResponse.data.id;

      // Persist extras to sessionStorage for checkout
      if (selectedExtras.length > 0) {
        sessionStorage.setItem(`checkout-extras-${holdId}`, JSON.stringify(selectedExtras));
      }

      // Then add the hold to the cart
      await addToCartMutation.mutateAsync(holdId);

      // Show success message and reset selection
      setShowCartSuccess(true);
      setShowBookingFlow(false);
      setSelectedDate(undefined);
      setSelectedSlot(undefined);

      // Hide success message after 5 seconds
      setTimeout(() => setShowCartSuccess(false), 5000);
    } catch (err) {
      console.error('Failed to add to cart:', err);
    }
  };

  // Accommodation-specific handlers
  const handleAccommodationCreateHold = async (checkIn: Date, checkOut: Date, guests: number) => {
    console.log('[AccommodationBooking] handleAccommodationCreateHold called', {
      checkIn,
      checkOut,
      guests,
    });

    // Clear any previous error
    setBookingError(null);

    console.log('[AccommodationBooking] Checking availability data:', {
      hasData: !!availabilityData,
      length: availabilityData?.length,
    });

    if (!availabilityData || availabilityData.length === 0) {
      console.log('[AccommodationBooking] EARLY RETURN: No availability data');
      setBookingError(tBooking('no_availability'));
      return;
    }

    // For accommodations, we use the check-in date to find a slot
    const checkInDateStr = format(checkIn, 'yyyy-MM-dd');
    const slot = availabilityData.find((s) => s.start.startsWith(checkInDateStr));

    console.log('[AccommodationBooking] Slot search:', {
      checkInDateStr,
      foundSlot: !!slot,
      slotId: slot?.id,
    });

    if (!slot) {
      console.log('[AccommodationBooking] EARLY RETURN: No slot found');
      setBookingError(tBooking('date_not_available'));
      return;
    }

    // Track if we should navigate after the operation
    let shouldNavigate = false;

    try {
      console.log('[AccommodationBooking] Creating hold...');
      const sessionId = getGuestSessionId();
      const response = await createHoldMutation.mutateAsync({
        slotId: String(slot.id),
        // For accommodations, we pass check-in/check-out dates and guest count
        check_in_date: format(checkIn, 'yyyy-MM-dd'),
        check_out_date: format(checkOut, 'yyyy-MM-dd'),
        guests,
        person_types: { adult: guests }, // Fallback for legacy compatibility
        session_id: sessionId,
        extras: selectedExtras,
      });
      const holdId = response.data.id;
      console.log('[AccommodationBooking] Hold created:', holdId);

      // Persist extras to sessionStorage for checkout
      if (selectedExtras.length > 0) {
        sessionStorage.setItem(`checkout-extras-${holdId}`, JSON.stringify(selectedExtras));
      }

      // Add to cart
      console.log('[AccommodationBooking] Adding to cart...');
      await addToCartMutation.mutateAsync(holdId);
      console.log('[AccommodationBooking] Added to cart');

      // Navigate to checkout using window.location for guaranteed navigation
      // Note: refetchQueries can hang indefinitely - the mutation's onSuccess already invalidates cart
      window.location.href = locale === 'fr' ? '/cart/checkout' : `/${locale}/cart/checkout`;
    } catch (err) {
      console.error('[AccommodationBooking] Error:', err);
      setBookingError(tBooking('booking_error'));
    }
  };

  const handleAccommodationAddToCart = async (checkIn: Date, checkOut: Date, guests: number) => {
    // Clear any previous error
    setBookingError(null);

    if (!availabilityData || availabilityData.length === 0) {
      setBookingError(tBooking('no_availability'));
      return;
    }

    // For accommodations, we use the check-in date to find a slot
    const checkInDateStr = format(checkIn, 'yyyy-MM-dd');
    const slot = availabilityData.find((s) => s.start.startsWith(checkInDateStr));

    if (!slot) {
      setBookingError(tBooking('date_not_available'));
      return;
    }

    try {
      const sessionId = getGuestSessionId();
      const holdResponse = await createHoldMutation.mutateAsync({
        slotId: String(slot.id),
        check_in_date: format(checkIn, 'yyyy-MM-dd'),
        check_out_date: format(checkOut, 'yyyy-MM-dd'),
        guests,
        person_types: { adult: guests },
        session_id: sessionId,
        extras: selectedExtras,
      });
      const holdId = holdResponse.data.id;

      // Persist extras to sessionStorage
      if (selectedExtras.length > 0) {
        sessionStorage.setItem(`checkout-extras-${holdId}`, JSON.stringify(selectedExtras));
      }

      // Add to cart
      await addToCartMutation.mutateAsync(holdId);

      // Show success message and reset
      setShowCartSuccess(true);
      setShowBookingFlow(false);
      setSelectedExtras([]);

      setTimeout(() => setShowCartSuccess(false), 5000);
    } catch (err) {
      console.error('Failed to add accommodation to cart:', err);
      setBookingError(tBooking('add_to_cart_failed'));
    }
  };

  const tr = (field: any) => resolveTranslation(field, locale);
  const title = tr(listing.title);
  const description = tr(listing.description);
  const maxCapacity = selectedSlot?.remainingCapacity || listing.maxGroupSize || 10;

  return (
    <MainLayout locale={locale}>
      {/* Cart Success Toast */}
      {showCartSuccess && (
        <div className="fixed top-20 right-4 z-50 animate-in slide-in-from-right duration-300">
          <div className="bg-[#1B2A4E] text-white px-6 py-4 rounded-lg shadow-2xl flex items-center gap-4">
            <CheckCircle className="h-6 w-6 flex-shrink-0" />
            <div>
              <p className="font-semibold">{tCart('added_to_cart')}</p>
              <div className="flex gap-3 mt-2">
                <button
                  onClick={() => router.push(`/cart`)}
                  className="text-sm underline hover:no-underline opacity-90 hover:opacity-100"
                >
                  {tCart('view_cart')}
                </button>
                <button
                  onClick={() => setShowCartSuccess(false)}
                  className="text-sm underline hover:no-underline opacity-90 hover:opacity-100"
                >
                  {tCart('continue_shopping')}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Booking Error Toast */}
      {bookingError && (
        <div className="fixed top-20 right-4 z-50 animate-in slide-in-from-right duration-300">
          <div className="bg-red-600 text-white px-6 py-4 rounded-lg shadow-2xl flex items-center gap-4">
            <XCircle className="h-6 w-6 flex-shrink-0" />
            <div>
              <p className="font-semibold">{bookingError}</p>
              <button
                onClick={() => setBookingError(null)}
                className="text-sm underline hover:no-underline opacity-90 hover:opacity-100 mt-2"
              >
                {tBooking('dismiss')}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Hero Section + Content - Unified Layout */}
      <div className="bg-neutral-50 min-h-screen">
        <div className="container mx-auto px-4 max-w-7xl">
          <div className="grid grid-cols-1 lg:grid-cols-[1fr_400px] gap-8 py-6">
            {/* Left Column: Hero + Content */}
            <div className="space-y-3">
              {/* Badge - Service-type colored */}
              <div>
                <span
                  className={cn(
                    'inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-semibold uppercase tracking-wider',
                    getServiceTypeBadgeClasses(listing.serviceType)
                  )}
                >
                  <span
                    className={cn(
                      'w-2 h-2 rounded-full',
                      getServiceTypeDotClasses(listing.serviceType)
                    )}
                  ></span>
                  {listing.serviceType === 'tour' || listing.serviceType === 'accommodation'
                    ? listing.activityType
                      ? tr(listing.activityType.name)
                      : listing.serviceType === 'accommodation'
                        ? t('default_accommodation_type')
                        : t('default_tour_type')
                    : listing.serviceType === 'nautical'
                      ? t('default_nautical_type')
                      : t('default_event_type')}
                </span>
              </div>

              {/* Title */}
              <h1 className="font-display text-3xl md:text-4xl lg:text-5xl font-bold text-heading leading-none">
                {title}
              </h1>

              {/* Meta Row - Service-type colored icons */}
              <div className="flex flex-wrap gap-4 text-sm text-body">
                <div className="flex items-center gap-2">
                  <MapPin
                    className={cn('h-4 w-4', getServiceTypeColors(listing.serviceType).accent)}
                  />
                  <span>{listing.meetingPoint?.address || t('default_location')}</span>
                </div>
                {(listing.serviceType === 'tour' || listing.serviceType === 'accommodation') &&
                  listing.duration && (
                    <div className="flex items-center gap-2">
                      <Clock
                        className={cn('h-4 w-4', getServiceTypeColors(listing.serviceType).accent)}
                      />
                      <span>
                        {listing.duration.value}{' '}
                        {t(
                          `duration_unit.${listing.duration.unit || (listing.serviceType === 'accommodation' ? 'days' : 'hours')}`
                        )}
                      </span>
                    </div>
                  )}
                {(listing.serviceType === 'tour' || listing.serviceType === 'accommodation') &&
                  listing.difficulty && (
                    <div className="flex items-center gap-2">
                      <svg
                        className={cn('h-4 w-4', getServiceTypeColors(listing.serviceType).accent)}
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          strokeWidth={2}
                          d="M13 10V3L4 14h7v7l9-11h-7z"
                        />
                      </svg>
                      <span className="capitalize">
                        {t(`difficulty_level.${listing.difficulty}`)}
                      </span>
                    </div>
                  )}
                <div className="flex items-center gap-2">
                  <Users
                    className={cn('h-4 w-4', getServiceTypeColors(listing.serviceType).accent)}
                  />
                  <span>{t('max_guests', { count: listing.maxGroupSize })}</span>
                </div>
                {listing.rating && (
                  <div className="flex items-center gap-2">
                    <Star
                      className={cn(
                        'h-4 w-4',
                        getServiceTypeColors(listing.serviceType).fill,
                        getServiceTypeColors(listing.serviceType).accent
                      )}
                    />
                    <span className="font-semibold">{listing.rating}</span>
                    <span>({tCommon('reviews', { count: listing.reviewsCount || 0 })})</span>
                  </div>
                )}
              </div>

              {/* Bento Gallery - use galleryImages if available, fall back to media */}
              {(() => {
                // Prefer galleryImages (new field), fall back to media (old relationship)
                const galleryImages =
                  listing.galleryImages && listing.galleryImages.length > 0
                    ? listing.galleryImages.map((path: string, i: number) => ({
                        id: `gallery-${i}`,
                        url: path,
                        alt: null,
                      }))
                    : listing.media || [];

                if (galleryImages.length === 0) return null;

                // Get layout count from galleryLayout or default to actual image count
                // Use type assertion since galleryLayout may not be in all Listing type variants
                const galleryLayout = (listing as any).galleryLayout;
                const layoutCount = galleryLayout
                  ? parseInt(String(galleryLayout))
                  : galleryImages.length;

                // Limit to actual available images
                const displayCount = Math.min(layoutCount, galleryImages.length);
                const displayImages = galleryImages.slice(0, displayCount);

                // Helper function to get grid classes based on layout
                const getBentoGridClasses = (count: number): string => {
                  switch (count) {
                    case 1:
                      return 'grid-cols-1 h-[300px]';
                    case 2:
                      return 'grid-cols-2 h-[300px]';
                    case 3:
                      return 'grid-cols-2 grid-rows-2 h-[400px]';
                    case 4:
                      return 'grid-cols-2 grid-rows-2 h-[400px]';
                    default:
                      return 'grid-cols-4 grid-rows-2 h-[400px]'; // 5+ images
                  }
                };

                // Helper function to get slot-specific classes
                const getSlotClasses = (index: number, count: number): string => {
                  // For 3-image layout, first image spans 2 rows
                  if (count === 3 && index === 0) return 'row-span-2';
                  // For 5-image layout, first image spans 2 cols and 2 rows
                  if (count >= 5 && index === 0) return 'col-span-2 row-span-2';
                  return '';
                };

                return (
                  <div className={`grid gap-2 ${getBentoGridClasses(displayCount)}`}>
                    {displayImages.map((media: any, index: number) => (
                      <button
                        key={media.id}
                        onClick={() => {
                          setLightboxIndex(index);
                          setLightboxOpen(true);
                        }}
                        className={`relative overflow-hidden rounded-lg group ${getSlotClasses(index, displayCount)}`}
                      >
                        <Image
                          src={normalizeMediaUrl(media.url)}
                          alt={tr(media.alt) || title}
                          fill
                          className="object-cover transition-transform duration-300 group-hover:scale-105"
                          priority={index === 0}
                        />
                        {/* "View all" overlay on last image if there are more images */}
                        {index === displayCount - 1 && galleryImages.length > displayCount && (
                          <div className="absolute inset-0 bg-black/60 flex items-center justify-center text-white font-semibold">
                            <div className="text-center">
                              <Camera className="h-6 w-6 mx-auto mb-2" />
                              <div>View all {galleryImages.length}</div>
                            </div>
                          </div>
                        )}
                      </button>
                    ))}
                  </div>
                );
              })()}

              {/* Accommodation Banner */}
              {listing.serviceType === 'accommodation' && listing.duration?.value && (
                <div className="mt-6 rounded-xl bg-amber-50 border border-amber-200 px-6 py-4 flex items-center gap-4">
                  <Calendar className="h-8 w-8 text-amber-600 flex-shrink-0" />
                  <div>
                    <p className="font-semibold text-amber-900 text-lg">
                      {listing.duration.value}-{t('day_experience')}
                    </p>
                    <p className="text-sm text-amber-700">{t('accommodation_booking_note')}</p>
                  </div>
                  <div className="ml-auto flex-shrink-0">
                    <ChevronRight className="h-6 w-6 text-amber-600 animate-bounce hidden lg:block" />
                    <ChevronDown className="h-6 w-6 text-amber-600 animate-bounce lg:hidden" />
                  </div>
                </div>
              )}

              {/* Main Content Sections */}
              <div className="border-t border-neutral-200 pt-12 mt-8">
                <div className="space-y-16">
                  {/* Description Section - only show if description exists */}
                  {typeof description === 'string' && description.trim() !== '' && (
                    <section>
                      <h2 className="font-display text-4xl font-bold text-heading mb-6 tracking-tight">
                        {t('about_experience')}
                      </h2>
                      <SanitizedHtml
                        html={description}
                        className="font-sans text-lg text-neutral-700 leading-relaxed prose prose-neutral max-w-none prose-p:my-2 prose-ul:my-2 prose-ol:my-2 prose-li:my-1"
                      />
                    </section>
                  )}

                  {/* Highlights */}
                  {listing.highlights && listing.highlights.length > 0 && (
                    <section>
                      <h2 className="font-display text-3xl font-bold text-heading mb-6 tracking-tight">
                        {t('experience_highlights')}
                      </h2>
                      <ul className="space-y-4">
                        {listing.highlights.map((highlight: any, index: number) => (
                          <li key={index} className="flex items-start gap-4">
                            <div
                              className={cn(
                                'flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center mt-1',
                                getServiceTypeColors(listing.serviceType).bg
                              )}
                            >
                              <CheckCircle
                                className={cn(
                                  'h-4 w-4',
                                  getServiceTypeColors(listing.serviceType).accent
                                )}
                              />
                            </div>
                            <span className="font-sans text-lg text-neutral-700 leading-relaxed">
                              {tr(highlight)}
                            </span>
                          </li>
                        ))}
                      </ul>
                    </section>
                  )}

                  {/* Itinerary & Map Section - Shows for all service types */}
                  {'itinerary' in listing && listing.itinerary && listing.itinerary.length > 0 && (
                    <section>
                      <h2 className="font-display text-3xl font-bold text-heading mb-6 tracking-tight">
                        {listing.serviceType === 'accommodation'
                          ? t('day_by_day_program')
                          : t('route_itinerary')}
                      </h2>

                      {/* Tabs */}
                      <RouteItineraryTabs
                        itinerary={(listing.itinerary as any[]).map((stop: any, index: number) => {
                          // GeoJSON coordinates are [lng, lat]
                          const coords = stop.coordinates?.coordinates;
                          const lat = stop.lat || (coords ? coords[1] : 0);
                          const lng = stop.lng || (coords ? coords[0] : 0);
                          return {
                            id: stop.id || index.toString(),
                            listingId: listing.id,
                            order: index,
                            title: stop.title,
                            description: stop.description,
                            durationMinutes: stop.durationMinutes || stop.duration || null,
                            stopType:
                              index === 0
                                ? 'start'
                                : index === (listing.itinerary as any[]).length - 1
                                  ? 'end'
                                  : 'waypoint',
                            lat,
                            lng,
                            elevationMeters: stop.elevationMeters || null,
                            photos: stop.photos || [],
                            day: stop.day ?? undefined,
                          };
                        })}
                        center={(() => {
                          const firstStop = (listing.itinerary as any[])[0];
                          const coords = firstStop?.coordinates?.coordinates;
                          return [
                            firstStop?.lat || (coords ? coords[1] : 36.8),
                            firstStop?.lng || (coords ? coords[0] : 10.2),
                          ] as [number, number];
                        })()}
                        title={title}
                        imageUrl={normalizeMediaUrl(listing.media?.[0]?.url)}
                        locale={locale}
                        isAccommodation={listing.serviceType === 'accommodation'}
                        skipRouting={[
                          'fishing',
                          'boat',
                          'diving',
                          'snorkeling',
                          'sailing',
                          'kayak',
                        ].some((w) => listing.activityType?.slug?.includes(w))}
                        routingProfile={
                          ['car', 'drive', 'quad', '4x4', 'buggy'].some((w) =>
                            listing.activityType?.slug?.includes(w)
                          )
                            ? 'driving'
                            : ['cycling', 'bike', 'velo', 'vélo'].some((w) =>
                                  listing.activityType?.slug?.includes(w)
                                )
                              ? 'cycling'
                              : 'foot'
                        }
                        serviceType={listing.serviceType}
                        mapDisplayType={listing.mapDisplayType}
                      />

                      {/* Elevation Profile */}
                      {'hasElevationProfile' in listing &&
                        listing.hasElevationProfile &&
                        (listing.itinerary as any[]).some((stop: any) => stop.elevationMeters) && (
                          <div className="mt-8">
                            <ElevationProfile
                              checkpoints={(listing.itinerary as any[]).map(
                                (stop: any, index: number) => {
                                  const coords = stop.coordinates?.coordinates;
                                  const lat = stop.lat || (coords ? coords[1] : 0);
                                  const lng = stop.lng || (coords ? coords[0] : 0);
                                  return {
                                    id: (stop.order ?? index).toString(),
                                    listingId: listing.id,
                                    order: stop.order ?? index,
                                    title: stop.title,
                                    description: stop.description,
                                    durationMinutes: stop.duration,
                                    stopType:
                                      (stop.order ?? index) === 0
                                        ? 'start'
                                        : (stop.order ?? index) ===
                                            (listing.itinerary as any[]).length - 1
                                          ? 'end'
                                          : 'waypoint',
                                    lat,
                                    lng,
                                    elevationMeters: stop.elevationMeters || null,
                                    photos: [],
                                  };
                                }
                              )}
                              locale={locale}
                              profile={(() => {
                                const itineraryArr = listing.itinerary as any[];
                                // Helper to get lat/lng from GeoJSON or direct props
                                const getCoords = (stop: any) => {
                                  const coords = stop.coordinates?.coordinates;
                                  return {
                                    lat: stop.lat || (coords ? coords[1] : 0),
                                    lng: stop.lng || (coords ? coords[0] : 0),
                                  };
                                };
                                // Calculate elevation profile from itinerary
                                const points = itineraryArr.map((stop: any, index: number) => {
                                  let distance = 0;
                                  if (index > 0) {
                                    // Calculate cumulative distance using Haversine formula
                                    for (let i = 1; i <= index; i++) {
                                      const prev = getCoords(itineraryArr[i - 1]);
                                      const curr = getCoords(itineraryArr[i]);
                                      const R = 6371000; // Earth's radius in meters
                                      const dLat = ((curr.lat - prev.lat) * Math.PI) / 180;
                                      const dLon = ((curr.lng - prev.lng) * Math.PI) / 180;
                                      const a =
                                        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                                        Math.cos((prev.lat * Math.PI) / 180) *
                                          Math.cos((curr.lat * Math.PI) / 180) *
                                          Math.sin(dLon / 2) *
                                          Math.sin(dLon / 2);
                                      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                                      distance += R * c;
                                    }
                                  }
                                  return {
                                    distance,
                                    elevation: stop.elevationMeters || 0,
                                  };
                                });

                                // Calculate total ascent/descent
                                let totalAscent = 0;
                                let totalDescent = 0;
                                for (let i = 1; i < points.length; i++) {
                                  const diff = points[i].elevation - points[i - 1].elevation;
                                  if (diff > 0) totalAscent += diff;
                                  else totalDescent += Math.abs(diff);
                                }

                                const elevations = points.map((p) => p.elevation);
                                return {
                                  listingId: listing.id,
                                  points,
                                  totalAscent,
                                  totalDescent,
                                  maxElevation: Math.max(...elevations),
                                  minElevation: Math.min(...elevations),
                                  totalDistance: points[points.length - 1]?.distance || 0,
                                };
                              })()}
                            />
                          </div>
                        )}
                    </section>
                  )}

                  {/* Reviews Section - Service-type colored */}
                  <ReviewsSection
                    listingSlug={slug}
                    rating={listing.rating ?? undefined}
                    reviewsCount={listing.reviewsCount || 0}
                    serviceType={listing.serviceType}
                  />

                  {/* Included / Not Included */}
                  <section className="grid md:grid-cols-2 gap-12">
                    {listing.included && listing.included.length > 0 && (
                      <div>
                        <h3 className="font-display text-2xl font-bold text-heading mb-6 tracking-tight">
                          {t('included')}
                        </h3>
                        <ul className="space-y-3">
                          {listing.included.map((item: any, index: number) => (
                            <li key={index} className="flex items-start gap-3">
                              <CheckCircle
                                className={cn(
                                  'h-5 w-5 flex-shrink-0 mt-0.5',
                                  getServiceTypeColors(listing.serviceType).accent
                                )}
                              />
                              <span className="font-sans text-neutral-700">{tr(item)}</span>
                            </li>
                          ))}
                        </ul>
                      </div>
                    )}

                    {listing.notIncluded && listing.notIncluded.length > 0 && (
                      <div>
                        <h3 className="font-display text-2xl font-bold text-heading mb-6 tracking-tight">
                          {t('not_included')}
                        </h3>
                        <ul className="space-y-3">
                          {listing.notIncluded.map((item: any, index: number) => (
                            <li key={index} className="flex items-start gap-3">
                              <XCircle className="h-5 w-5 text-error flex-shrink-0 mt-0.5" />
                              <span className="font-sans text-neutral-700">{tr(item)}</span>
                            </li>
                          ))}
                        </ul>
                      </div>
                    )}
                  </section>

                  {/* Available Extras/Add-ons Preview */}
                  {listing.extras && listing.extras.length > 0 && (
                    <section id="extras">
                      <h3 className="font-display text-2xl font-bold text-heading mb-6 tracking-tight flex items-center gap-3">
                        <Package
                          className={cn(
                            'h-6 w-6',
                            getServiceTypeColors(listing.serviceType).accent
                          )}
                        />
                        {t('available_extras')}
                      </h3>
                      <p className="text-neutral-600 mb-6">{t('extras_description')}</p>
                      <div className="grid gap-4 sm:grid-cols-2">
                        {listing.extras.slice(0, 4).map((extra: any) => {
                          const currency = listing.pricing?.displayCurrency || 'EUR';
                          const price = currency === 'TND' ? extra.priceTnd : extra.priceEur;
                          const pricingTypeLabel =
                            extra.pricingType === 'per_person'
                              ? t('per_person')
                              : extra.pricingType === 'per_booking'
                                ? t('per_booking')
                                : t('per_unit');

                          return (
                            <div
                              key={extra.id}
                              className="border border-neutral-200 rounded-lg p-4 bg-white hover:shadow-md transition-shadow"
                            >
                              <div className="flex justify-between items-start gap-4">
                                <div className="flex-1">
                                  <h4 className="font-semibold text-heading">{tr(extra.name)}</h4>
                                  {extra.shortDescription && (
                                    <p className="text-sm text-neutral-600 mt-1 line-clamp-2">
                                      {tr(extra.shortDescription)}
                                    </p>
                                  )}
                                </div>
                                <div className="text-right flex-shrink-0">
                                  <span
                                    className={cn(
                                      'font-bold text-lg',
                                      getServiceTypeColors(listing.serviceType).accent
                                    )}
                                  >
                                    {price?.toFixed(2)} {currency}
                                  </span>
                                  <span className="text-xs text-neutral-500 block">
                                    {pricingTypeLabel}
                                  </span>
                                </div>
                              </div>
                            </div>
                          );
                        })}
                      </div>
                      {listing.extras.length > 4 && (
                        <p className="text-sm text-neutral-500 mt-4 text-center">
                          {t('more_extras_available', { count: listing.extras.length - 4 })}
                        </p>
                      )}
                    </section>
                  )}

                  {/* Requirements */}
                  {listing.requirements && listing.requirements.length > 0 && (
                    <section>
                      <h3 className="font-display text-2xl font-bold text-heading mb-6 tracking-tight">
                        {t('important_requirements')}
                      </h3>
                      <ul className="space-y-3">
                        {listing.requirements.map((req: any, index: number) => (
                          <li key={index} className="flex items-start gap-3">
                            <AlertCircle className="h-5 w-5 text-warning flex-shrink-0 mt-0.5" />
                            <span className="font-sans text-neutral-700">{tr(req)}</span>
                          </li>
                        ))}
                      </ul>
                    </section>
                  )}

                  {/* Accommodation Details (for house rentals) */}
                  {listing.serviceType === 'accommodation' && listing.accommodation && (
                    <AccommodationDetailsSection accommodation={listing.accommodation} />
                  )}

                  {/* Nautical Details (for boat rentals) */}
                  {listing.serviceType === 'nautical' && listing.nautical && (
                    <NauticalDetailsSection nautical={listing.nautical} />
                  )}

                  {/* Safety & Accessibility */}
                  <section className="grid md:grid-cols-2 gap-8">
                    <SafetySection safety={listing.safetyInfo} />
                    <AccessibilitySection accessibility={listing.accessibilityInfo} />
                  </section>

                  {/* Cancellation Policy */}
                  <CancellationPolicyCard policy={listing.cancellationPolicy} />

                  {/* FAQs */}
                  {listing.faqs && listing.faqs.length > 0 && <FAQSection faqs={listing.faqs} />}
                </div>
              </div>
            </div>

            {/* Right Column: Sticky Booking Panel (visible from hero through all content) */}
            <div className="hidden lg:block">
              <FixedBookingPanel
                listing={listing}
                availabilityData={availabilityData}
                hasActualReviews={false}
              >
                {isAccommodationListing ? (
                  <AccommodationBookingFlowContent
                    isLoadingAvailability={isLoadingAvailability}
                    availabilityData={availabilityData}
                    listing={listing}
                    createHoldMutation={createHoldMutation}
                    onCreateHold={handleAccommodationCreateHold}
                    onAddToCart={handleAccommodationAddToCart}
                    isAddingToCart={addToCartMutation.isPending}
                    locale={locale}
                    tAccommodation={tAccommodation}
                    tCommon={tCommon}
                    tBooking={tBooking}
                    tCart={tCart}
                    selectedExtras={selectedExtras}
                    extrasExpanded={extrasExpanded}
                    onExtrasExpandedChange={setExtrasExpanded}
                    onExtraIncrement={incrementExtra}
                    onExtraDecrement={decrementExtra}
                    getExtraQuantity={getExtraQuantity}
                  />
                ) : (
                  <BookingFlowContent
                    isLoadingAvailability={isLoadingAvailability}
                    availabilityData={availabilityData}
                    selectedDate={selectedDate}
                    selectedSlot={selectedSlot}
                    slotsForSelectedDate={slotsForSelectedDate}
                    personTypes={personTypes}
                    personTypeBreakdown={personTypeBreakdown}
                    maxCapacity={maxCapacity}
                    listing={listing}
                    createHoldMutation={createHoldMutation}
                    onDateSelect={handleDateSelect}
                    onSlotSelect={handleSlotSelect}
                    onPersonTypeChange={setPersonTypeBreakdown}
                    onCreateHold={handleCreateHold}
                    onAddToCart={handleAddToCart}
                    isAddingToCart={addToCartMutation.isPending}
                    onClose={() => setShowBookingFlow(false)}
                    locale={locale}
                    t={t}
                    tAvail={tAvail}
                    tBooking={tBooking}
                    tCommon={tCommon}
                    tCart={tCart}
                    selectedExtras={selectedExtras}
                    extrasExpanded={extrasExpanded}
                    onExtrasExpandedChange={setExtrasExpanded}
                    onExtraIncrement={incrementExtra}
                    onExtraDecrement={decrementExtra}
                    getExtraQuantity={getExtraQuantity}
                  />
                )}
              </FixedBookingPanel>
            </div>
          </div>
        </div>
      </div>

      {/* Mobile Bottom Bar */}
      <div className="lg:hidden">
        <BookingPanel
          pricing={listing.pricing}
          serviceType={listing.serviceType}
          isOpen={showBookingFlow}
          onOpenChange={setShowBookingFlow}
        >
          {isAccommodationListing ? (
            <AccommodationBookingFlowContent
              isLoadingAvailability={isLoadingAvailability}
              availabilityData={availabilityData}
              listing={listing}
              createHoldMutation={createHoldMutation}
              onCreateHold={handleAccommodationCreateHold}
              onAddToCart={handleAccommodationAddToCart}
              isAddingToCart={addToCartMutation.isPending}
              locale={locale}
              tAccommodation={tAccommodation}
              tCommon={tCommon}
              tBooking={tBooking}
              tCart={tCart}
              selectedExtras={selectedExtras}
              extrasExpanded={extrasExpanded}
              onExtrasExpandedChange={setExtrasExpanded}
              onExtraIncrement={incrementExtra}
              onExtraDecrement={decrementExtra}
              getExtraQuantity={getExtraQuantity}
            />
          ) : (
            <BookingFlowContent
              isLoadingAvailability={isLoadingAvailability}
              availabilityData={availabilityData}
              selectedDate={selectedDate}
              selectedSlot={selectedSlot}
              slotsForSelectedDate={slotsForSelectedDate}
              personTypes={personTypes}
              personTypeBreakdown={personTypeBreakdown}
              maxCapacity={maxCapacity}
              listing={listing}
              createHoldMutation={createHoldMutation}
              onDateSelect={handleDateSelect}
              onSlotSelect={handleSlotSelect}
              onPersonTypeChange={setPersonTypeBreakdown}
              onCreateHold={handleCreateHold}
              onAddToCart={handleAddToCart}
              isAddingToCart={addToCartMutation.isPending}
              onClose={() => setShowBookingFlow(false)}
              locale={locale}
              t={t}
              tAvail={tAvail}
              tBooking={tBooking}
              tCommon={tCommon}
              tCart={tCart}
              selectedExtras={selectedExtras}
              extrasExpanded={extrasExpanded}
              onExtrasExpandedChange={setExtrasExpanded}
              onExtraIncrement={incrementExtra}
              onExtraDecrement={decrementExtra}
              getExtraQuantity={getExtraQuantity}
            />
          )}
        </BookingPanel>
      </div>

      {/* Image Lightbox */}
      {lightboxOpen && (
        <ImageLightbox
          images={
            listing.galleryImages && listing.galleryImages.length > 0
              ? listing.galleryImages.map((path: string, i: number) => ({
                  id: `gallery-${i}`,
                  url: path,
                  alt: '',
                  type: 'image' as const,
                  order: i,
                  thumbnailUrl: null,
                  category: 'gallery' as const,
                }))
              : listing.media || []
          }
          initialIndex={lightboxIndex}
          isOpen={lightboxOpen}
          onClose={() => setLightboxOpen(false)}
        />
      )}
    </MainLayout>
  );
}
