'use client';

import { useState, useMemo, useEffect } from 'react';
import Image from 'next/image';
import dynamic from 'next/dynamic';
import { useRouter } from '@/i18n/navigation';
import { useTranslations } from 'next-intl';
import { format, addMonths } from 'date-fns';
import { MainLayout } from '@/components/templates/MainLayout';
import { useAvailability, useCreateHold, useAddToCart } from '@/lib/api/hooks';
import { Button } from '@go-adventure/ui';
import { PersonTypeSelector } from '@/components/booking/PersonTypeSelector';
import { BookingStepIndicator, type BookingStep } from '@/components/booking/BookingStepIndicator';
import DOMPurify from 'dompurify';

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
} from 'lucide-react';
import { resolveTranslation } from '@/lib/utils/translate';
import { getGuestSessionId } from '@/lib/utils/session';
import type { AvailabilitySlot, Listing, PersonType } from '@go-adventure/schemas';

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

// Get person types from listing pricing or return defaults
function getPersonTypesFromListing(listing: Listing): PersonType[] {
  const pricing = listing.pricing || {};
  const personTypes = pricing.personTypes;

  // Get base price for fallback
  const basePrice = pricing.displayPrice || pricing.tndPrice || 0;
  const numericPrice = typeof basePrice === 'string' ? parseFloat(basePrice) : basePrice;

  if (personTypes && Array.isArray(personTypes) && personTypes.length > 0) {
    // Fill in missing 'price' field with displayPrice/tndPrice/eurPrice
    return personTypes.map((pt: any) => ({
      ...pt,
      price: pt.price ?? pt.displayPrice ?? pt.tndPrice ?? pt.eurPrice ?? numericPrice,
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

// Calculate total from person type breakdown
function calculateTotalFromBreakdown(
  personTypes: PersonType[],
  breakdown: Record<string, number>
): { totalGuests: number; totalPrice: number } {
  let totalGuests = 0;
  let totalPrice = 0;

  for (const type of personTypes) {
    const quantity = breakdown[type.key] || 0;
    totalGuests += quantity;
    totalPrice += (type.price ?? 0) * quantity;
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
}: BookingFlowContentProps) {
  const [wizardStep, setWizardStep] = useState<1 | 2 | 3>(1);

  // Calculate totals from breakdown
  const { totalGuests, totalPrice } = calculateTotalFromBreakdown(personTypes, personTypeBreakdown);
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

      {/* STEP 2: Time Slots Only */}
      {wizardStep === 2 && (
        <div>
          <h3 className="font-semibold text-heading mb-4">{tAvail('select_time')}</h3>
          {slotsForSelectedDate.length > 0 ? (
            <div className="space-y-2">
              {slotsForSelectedDate.map((slot) => {
                const remainingCapacity = slot.remainingCapacity ?? slot.capacity;
                const isLowCapacity = remainingCapacity <= 3;
                const isAlmostFull = remainingCapacity <= 5;

                return (
                  <button
                    key={slot.id}
                    onClick={() => handleSlotSelect(slot)}
                    className={`w-full p-3 rounded-lg border text-left transition-colors ${
                      selectedSlot?.id === slot.id
                        ? 'border-primary bg-primary/5'
                        : 'border-neutral-200 hover:border-neutral-300'
                    }`}
                  >
                    <div className="flex items-center justify-between mb-1">
                      <span className="font-medium">{format(new Date(slot.start), 'HH:mm')}</span>
                      <span
                        className={`text-sm font-semibold ${
                          isLowCapacity
                            ? 'text-error-dark'
                            : isAlmostFull
                              ? 'text-warning-dark'
                              : 'text-success-dark'
                        }`}
                      >
                        {remainingCapacity} {tAvail('available')}
                      </span>
                    </div>
                    {isLowCapacity && (
                      <div className="text-xs text-error-dark font-medium">
                        ⚠️ Only {remainingCapacity} spot{remainingCapacity !== 1 ? 's' : ''} left!
                      </div>
                    )}
                    {isAlmostFull && !isLowCapacity && (
                      <div className="text-xs text-warning-dark">🔥 Filling up fast</div>
                    )}
                  </button>
                );
              })}
            </div>
          ) : (
            <p className="text-center text-neutral-500 py-4">{tAvail('no_slots_available')}</p>
          )}
        </div>
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
            currency={selectedSlot.currency || listing.pricing?.currency || 'EUR'}
            maxCapacity={maxCapacity}
            locale={locale}
          />

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

// Route & Itinerary Tabs Component
interface RouteItineraryTabsProps {
  itinerary: any[];
  center: [number, number];
  title: string;
  imageUrl?: string;
  locale: string;
}

function RouteItineraryTabs({
  itinerary,
  center,
  title,
  imageUrl,
  locale,
}: RouteItineraryTabsProps) {
  const [activeTab, setActiveTab] = useState<'map' | 'itinerary'>('map');

  return (
    <div className="space-y-4">
      {/* Tab Buttons */}
      <div className="flex gap-2 border-b border-neutral-200">
        <button
          onClick={() => setActiveTab('map')}
          className={`px-6 py-3 font-semibold transition-colors relative ${
            activeTab === 'map'
              ? 'text-primary border-b-2 border-primary'
              : 'text-neutral-600 hover:text-neutral-900'
          }`}
        >
          Trail Map
        </button>
        <button
          onClick={() => setActiveTab('itinerary')}
          className={`px-6 py-3 font-semibold transition-colors relative ${
            activeTab === 'itinerary'
              ? 'text-primary border-b-2 border-primary'
              : 'text-neutral-600 hover:text-neutral-900'
          }`}
        >
          Itinerary
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
              className="h-full w-full"
            />
          </div>
        )}

        {activeTab === 'itinerary' && (
          <div className="bg-white rounded-lg border border-neutral-200 p-6">
            <ItineraryTimeline stops={itinerary} locale={locale} />
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
  const t = useTranslations('listing');
  const tCommon = useTranslations('common');
  const tAvail = useTranslations('availability');
  const tBooking = useTranslations('booking');
  const tCart = useTranslations('cart');

  // Booking flow state
  const [showBookingFlow, setShowBookingFlow] = useState(false);
  const [selectedDate, setSelectedDate] = useState<Date | undefined>();
  const [selectedSlot, setSelectedSlot] = useState<AvailabilitySlot | undefined>();
  const [personTypeBreakdown, setPersonTypeBreakdown] = useState<Record<string, number>>({
    adult: 1,
  });

  // Lightbox state
  const [lightboxOpen, setLightboxOpen] = useState(false);
  const [lightboxIndex, setLightboxIndex] = useState(0);

  // Get person types from listing (memoized)
  const personTypes = useMemo(() => {
    return getPersonTypesFromListing(listing);
  }, [listing]);

  // Get availability for the next 3 months (always fetch since booking panel is visible from start)
  const startDate = format(new Date(), 'yyyy-MM-dd');
  const endDate = format(addMonths(new Date(), 3), 'yyyy-MM-dd');
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
    console.log('Creating hold with breakdown:', {
      filteredBreakdown,
      totalGuests,
      slotCapacity: selectedSlot.remainingCapacity,
    });

    try {
      const sessionId = getGuestSessionId();
      const response = await createHoldMutation.mutateAsync({
        slotId: String(selectedSlot.id),
        person_types: filteredBreakdown,
        session_id: sessionId,
        extras: [],
      });
      const holdId = response.data.id;

      // Add to cart in background (for abandoned cart marketing)
      await addToCartMutation.mutateAsync(holdId);

      // Redirect to dedicated checkout page with hold ID (skip cart view)
      router.push(`/checkout/${holdId}`);
    } catch (err) {
      console.error('Failed to create hold:', err);
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
      // First create a hold
      const holdResponse = await createHoldMutation.mutateAsync({
        slotId: String(selectedSlot.id),
        person_types: filteredBreakdown,
        session_id: sessionId,
        extras: [],
      });
      const holdId = holdResponse.data.id;

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

  const tr = (field: any) => resolveTranslation(field, locale);
  const title = tr(listing.title);
  const description = tr(listing.description);
  const maxCapacity = selectedSlot?.remainingCapacity || listing.maxGroupSize || 10;

  return (
    <MainLayout locale={locale}>
      {/* Cart Success Toast */}
      {showCartSuccess && (
        <div className="fixed top-20 right-4 z-50 animate-in slide-in-from-right duration-300">
          <div className="bg-[#0D642E] text-white px-6 py-4 rounded-lg shadow-2xl flex items-center gap-4">
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

      {/* Hero Section + Content - Unified Layout */}
      <div className="bg-accent min-h-screen">
        <div className="container mx-auto px-4 max-w-7xl">
          <div className="grid grid-cols-1 lg:grid-cols-[1fr_400px] gap-8 py-6">
            {/* Left Column: Hero + Content */}
            <div className="space-y-3">
              {/* Badge */}
              <div>
                <span className="inline-flex items-center gap-2 px-3 py-1.5 bg-primary/10 text-primary rounded-full text-sm font-semibold uppercase tracking-wider">
                  <span className="w-2 h-2 bg-secondary rounded-full"></span>
                  {listing.serviceType === 'tour' ? 'Guided Tour' : 'Special Event'}
                </span>
              </div>

              {/* Title */}
              <h1 className="font-display text-3xl md:text-4xl lg:text-5xl font-bold text-heading leading-none">
                {title}
              </h1>

              {/* Meta Row */}
              <div className="flex flex-wrap gap-4 text-sm text-body">
                <div className="flex items-center gap-2">
                  <MapPin className="h-4 w-4 text-primary" />
                  <span>{listing.meetingPoint?.address || 'Tunisia'}</span>
                </div>
                {listing.serviceType === 'tour' && listing.duration && (
                  <div className="flex items-center gap-2">
                    <Clock className="h-4 w-4 text-primary" />
                    <span>
                      {listing.duration.value} {listing.duration.unit}
                    </span>
                  </div>
                )}
                <div className="flex items-center gap-2">
                  <Users className="h-4 w-4 text-primary" />
                  <span>Max {listing.maxGroupSize} guests</span>
                </div>
                {listing.rating && (
                  <div className="flex items-center gap-2">
                    <Star className="h-4 w-4 fill-secondary text-secondary" />
                    <span className="font-semibold">{listing.rating}</span>
                    <span>({listing.reviewsCount || 0} reviews)</span>
                  </div>
                )}
              </div>

              {/* Bento Gallery */}
              {listing.media && listing.media.length > 0 && (
                <div className="grid grid-cols-4 grid-rows-2 gap-2 h-[400px]">
                  {/* Large image - takes 2x2 */}
                  {listing.media[0] && (
                    <button
                      onClick={() => {
                        setLightboxIndex(0);
                        setLightboxOpen(true);
                      }}
                      className="col-span-2 row-span-2 relative overflow-hidden rounded-lg group"
                    >
                      <Image
                        src={listing.media[0].url}
                        alt={tr(listing.media[0].alt) || title}
                        fill
                        className="object-cover transition-transform duration-300 group-hover:scale-105"
                        priority
                      />
                    </button>
                  )}
                  {/* Small images - 4 images in 2x2 grid on the right */}
                  {listing.media.slice(1, 5).map((media, index) => (
                    <button
                      key={media.id}
                      onClick={() => {
                        setLightboxIndex(index + 1);
                        setLightboxOpen(true);
                      }}
                      className="relative overflow-hidden rounded-lg group"
                    >
                      <Image
                        src={media.url}
                        alt={tr(media.alt) || title}
                        fill
                        className="object-cover transition-transform duration-300 group-hover:scale-105"
                      />
                      {/* "View all" overlay on last image */}
                      {index === 3 && listing.media.length > 5 && (
                        <div className="absolute inset-0 bg-black/60 flex items-center justify-center text-white font-semibold">
                          <div className="text-center">
                            <Camera className="h-6 w-6 mx-auto mb-2" />
                            <div>View all {listing.media.length}</div>
                          </div>
                        </div>
                      )}
                    </button>
                  ))}
                </div>
              )}

              {/* Main Content Sections */}
              <div className="border-t border-neutral-200 pt-12 mt-8">
                <div className="space-y-16">
                  {/* Description Section - only show if description exists */}
                  {description && description.trim() !== '' && (
                    <section>
                      <h2 className="font-display text-4xl font-bold text-heading mb-6 tracking-tight">
                        About This Experience
                      </h2>
                      <div
                        className="font-sans text-lg text-neutral-700 leading-relaxed prose prose-neutral max-w-none prose-p:my-2 prose-ul:my-2 prose-ol:my-2 prose-li:my-1"
                        dangerouslySetInnerHTML={{
                          __html: DOMPurify.sanitize(description, {
                            ALLOWED_TAGS: [
                              'p',
                              'br',
                              'strong',
                              'em',
                              'ul',
                              'ol',
                              'li',
                              'a',
                              'h2',
                              'h3',
                              'h4',
                            ],
                            ALLOWED_ATTR: ['href', 'target', 'rel'],
                          }),
                        }}
                      />
                    </section>
                  )}

                  {/* Highlights */}
                  {listing.highlights && listing.highlights.length > 0 && (
                    <section>
                      <h2 className="font-display text-3xl font-bold text-heading mb-6 tracking-tight">
                        Experience Highlights
                      </h2>
                      <ul className="space-y-4">
                        {listing.highlights.map((highlight: any, index: number) => (
                          <li key={index} className="flex items-start gap-4">
                            <div className="flex-shrink-0 w-6 h-6 rounded-full bg-secondary/20 flex items-center justify-center mt-1">
                              <CheckCircle className="h-4 w-4 text-primary" />
                            </div>
                            <span className="font-sans text-lg text-neutral-700 leading-relaxed">
                              {tr(highlight)}
                            </span>
                          </li>
                        ))}
                      </ul>
                    </section>
                  )}

                  {/* Itinerary & Map Section */}
                  {'itinerary' in listing && listing.itinerary && listing.itinerary.length > 0 && (
                    <section>
                      <h2 className="font-display text-3xl font-bold text-heading mb-6 tracking-tight">
                        Route & Itinerary
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
                        imageUrl={listing.media?.[0]?.url}
                        locale={locale}
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

                  {/* Reviews Section */}
                  <ReviewsSection
                    listingId={listing.id}
                    rating={listing.rating ?? undefined}
                    reviewsCount={listing.reviewsCount || 0}
                  />

                  {/* Included / Not Included */}
                  <section className="grid md:grid-cols-2 gap-12">
                    {listing.included && listing.included.length > 0 && (
                      <div>
                        <h3 className="font-display text-2xl font-bold text-heading mb-6 tracking-tight">
                          What's Included
                        </h3>
                        <ul className="space-y-3">
                          {listing.included.map((item: any, index: number) => (
                            <li key={index} className="flex items-start gap-3">
                              <CheckCircle className="h-5 w-5 text-primary flex-shrink-0 mt-0.5" />
                              <span className="font-sans text-neutral-700">{tr(item)}</span>
                            </li>
                          ))}
                        </ul>
                      </div>
                    )}

                    {listing.notIncluded && listing.notIncluded.length > 0 && (
                      <div>
                        <h3 className="font-display text-2xl font-bold text-heading mb-6 tracking-tight">
                          Not Included
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

                  {/* Requirements */}
                  {listing.requirements && listing.requirements.length > 0 && (
                    <section>
                      <h3 className="font-display text-2xl font-bold text-heading mb-6 tracking-tight">
                        Important Requirements
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
                />
              </FixedBookingPanel>
            </div>
          </div>
        </div>
      </div>

      {/* Mobile Bottom Bar */}
      <div className="lg:hidden">
        <BookingPanel
          pricing={listing.pricing}
          isOpen={showBookingFlow}
          onOpenChange={setShowBookingFlow}
        >
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
          />
        </BookingPanel>
      </div>

      {/* Image Lightbox */}
      {lightboxOpen && (
        <ImageLightbox
          images={listing.media}
          initialIndex={lightboxIndex}
          isOpen={lightboxOpen}
          onClose={() => setLightboxOpen(false)}
        />
      )}
    </MainLayout>
  );
}
