'use client';

import { useState, useMemo } from 'react';
import Image from 'next/image';
import { useParams } from 'next/navigation';
import { useRouter } from '@/i18n/navigation';
import { useTranslations } from 'next-intl';
import { format, addMonths } from 'date-fns';
import { MainLayout } from '@/components/templates/MainLayout';
import { useListing, useAvailability, useCreateHold } from '@/lib/api/hooks';
import { Button } from '@go-adventure/ui';
import { BookingPanel } from '@/components/booking/BookingPanel';
import { PriceDisplay } from '@/components/molecules/PriceDisplay';
import { PersonTypeSelector } from '@/components/booking/PersonTypeSelector';
import AvailabilityCalendar from '@/components/availability/AvailabilityCalendar';
import { MapPin, Clock, Users, Calendar, CheckCircle, XCircle, AlertCircle } from 'lucide-react';
import { resolveTranslation } from '@/lib/utils/translate';
import { getGuestSessionId } from '@/lib/utils/session';
import type { AvailabilitySlot, Listing } from '@go-adventure/schemas';

// Person type configuration
interface PersonType {
  key: string;
  label: { en: string; fr: string } | string;
  price: number;
  minAge: number | null;
  maxAge: number | null;
  minQuantity: number;
  maxQuantity: number | null;
}

// Get person types from listing pricing or return defaults
function getPersonTypesFromListing(listing: Listing): PersonType[] {
  const pricing = listing.pricing || {};
  const personTypes = pricing.personTypes;

  if (personTypes && Array.isArray(personTypes) && personTypes.length > 0) {
    return personTypes;
  }

  // Return defaults based on base price
  const basePrice = pricing.basePrice || 0;
  const numericPrice = typeof basePrice === 'string' ? parseFloat(basePrice) : basePrice;

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
    totalPrice += type.price * quantity;
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
  onClose: () => void;
  locale: string;
  t: ReturnType<typeof useTranslations>;
  tAvail: ReturnType<typeof useTranslations>;
  tBooking: ReturnType<typeof useTranslations>;
  tCommon: ReturnType<typeof useTranslations>;
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
  onClose,
  locale,
  t,
  tAvail,
  tBooking,
  tCommon,
}: BookingFlowContentProps) {
  // Calculate totals from breakdown
  const { totalGuests, totalPrice } = calculateTotalFromBreakdown(personTypes, personTypeBreakdown);
  const canProceed = totalGuests > 0;

  return (
    <div className="space-y-6">
      {/* Calendar */}
      <div>
        <h3 className="font-semibold text-neutral-900 mb-4">{tAvail('select_date')}</h3>
        {isLoadingAvailability ? (
          <p className="text-center text-neutral-500 py-8">{tCommon('loading')}</p>
        ) : (
          <AvailabilityCalendar
            slots={availabilityData || []}
            onDateSelect={onDateSelect}
            selectedDate={selectedDate}
          />
        )}
      </div>

      {/* Time Slots */}
      {selectedDate && slotsForSelectedDate.length > 0 && (
        <div>
          <h3 className="font-semibold text-neutral-900 mb-4">{tAvail('select_time')}</h3>
          <div className="space-y-2">
            {slotsForSelectedDate.map((slot) => (
              <button
                key={slot.id}
                onClick={() => onSlotSelect(slot)}
                className={`w-full p-3 rounded-lg border text-left transition-colors ${
                  selectedSlot?.id === slot.id
                    ? 'border-primary bg-primary/5'
                    : 'border-neutral-200 hover:border-neutral-300'
                }`}
              >
                <div className="flex items-center justify-between">
                  <span className="font-medium">{format(new Date(slot.start), 'HH:mm')}</span>
                  <span
                    className={`text-sm ${
                      slot.status === 'limited' ? 'text-yellow-600' : 'text-green-600'
                    }`}
                  >
                    {slot.remainingCapacity || slot.available} {tAvail('available')}
                  </span>
                </div>
              </button>
            ))}
          </div>
        </div>
      )}

      {selectedDate && slotsForSelectedDate.length === 0 && (
        <p className="text-center text-neutral-500 py-4">{tAvail('no_slots_available')}</p>
      )}

      {/* Person Type Selection (replaces simple participant counter) */}
      {selectedSlot && (
        <div>
          <h3 className="font-semibold text-neutral-900 mb-4">{tBooking('travelers')}</h3>
          <PersonTypeSelector
            personTypes={personTypes}
            value={personTypeBreakdown}
            onChange={onPersonTypeChange}
            currency={selectedSlot.currency || listing.pricing?.currency || 'EUR'}
            maxCapacity={maxCapacity}
            locale={locale}
          />
        </div>
      )}

      {/* Book Button */}
      {selectedSlot && (
        <div className="pt-4 border-t border-neutral-200">
          <Button
            variant="primary"
            size="lg"
            className="w-full"
            onClick={onCreateHold}
            disabled={createHoldMutation.isPending || !canProceed}
          >
            {createHoldMutation.isPending ? tCommon('loading') : tCommon('book_now')}
          </Button>
          {createHoldMutation.isError && (
            <p className="text-sm text-red-600 mt-2 text-center">
              {createHoldMutation.error?.message || tCommon('error')}
            </p>
          )}
        </div>
      )}
    </div>
  );
}

export default function ListingDetailPage() {
  const params = useParams();
  const router = useRouter();
  const locale = params?.locale as string;
  const slug = params?.slug as string;
  const t = useTranslations('listing');
  const tCommon = useTranslations('common');
  const tAvail = useTranslations('availability');
  const tBooking = useTranslations('booking');

  // Booking flow state
  const [showBookingFlow, setShowBookingFlow] = useState(false);
  const [selectedDate, setSelectedDate] = useState<Date | undefined>();
  const [selectedSlot, setSelectedSlot] = useState<AvailabilitySlot | undefined>();
  const [personTypeBreakdown, setPersonTypeBreakdown] = useState<Record<string, number>>({
    adult: 1,
  });

  const { data: listing, isLoading, error } = useListing(slug);

  // Get person types from listing (memoized)
  const personTypes = useMemo(() => {
    if (!listing) return [];
    return getPersonTypesFromListing(listing);
  }, [listing]);

  // Get availability for the next 3 months
  const startDate = format(new Date(), 'yyyy-MM-dd');
  const endDate = format(addMonths(new Date(), 3), 'yyyy-MM-dd');
  const { data: availabilityData, isLoading: isLoadingAvailability } = useAvailability(
    slug,
    startDate,
    endDate,
    showBookingFlow
  );

  const createHoldMutation = useCreateHold(slug);

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

    try {
      const sessionId = getGuestSessionId();
      const response = await createHoldMutation.mutateAsync({
        slotId: String(selectedSlot.id),
        person_types: filteredBreakdown,
        session_id: sessionId,
      });
      // Redirect to dedicated checkout page with hold ID
      const holdId = response.data.id;
      router.push(`/checkout/${holdId}`);
    } catch (err) {
      console.error('Failed to create hold:', err);
    }
  };

  if (isLoading) {
    return (
      <MainLayout locale={locale}>
        <div className="container mx-auto px-4 py-12">
          <p className="text-center text-lg text-neutral-500">{tCommon('loading')}</p>
        </div>
      </MainLayout>
    );
  }

  if (error || !listing) {
    return (
      <MainLayout locale={locale}>
        <div className="container mx-auto px-4 py-12">
          <p className="text-center text-lg text-red-500">{tCommon('error')}</p>
        </div>
      </MainLayout>
    );
  }

  const mainImage = listing.media[0];
  const tr = (field: any) => resolveTranslation(field, locale);
  const title = tr(listing.title);
  const description = tr(listing.description);
  const maxCapacity = selectedSlot?.remainingCapacity || listing.maxGroupSize || 10;

  return (
    <MainLayout locale={locale}>
      {/* Hero Header - 60vh */}
      <div className="relative h-[60vh] w-full bg-neutral-100">
        {mainImage && (
          <Image
            src={mainImage.url}
            alt={tr(mainImage.alt) || title}
            fill
            className="object-cover"
            priority
          />
        )}
        {/* Bottom Gradient */}
        <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent" />

        {/* Content - Bottom Left Container */}
        <div className="absolute bottom-0 left-0 w-full">
          <div className="container mx-auto px-4 pb-8">
            {/* Badge - Type */}
            <div className="mb-4">
              <span className="inline-block bg-primary text-white px-4 py-2 rounded-full text-sm font-bold uppercase tracking-wide">
                {listing.serviceType === 'tour' ? 'Tour' : 'Event'}
              </span>
            </div>

            {/* Title */}
            <h1 className="text-4xl md:text-5xl lg:text-6xl font-display font-bold text-white mb-4 max-w-4xl">
              {title}
            </h1>

            {/* Meta Row */}
            <div className="flex flex-wrap gap-6 text-white/90">
              <div className="flex items-center gap-2">
                <MapPin className="h-5 w-5" />
                <span>{listing.meetingPoint?.address || 'Tunisia'}</span>
              </div>
              {listing.serviceType === 'tour' && listing.duration && (
                <div className="flex items-center gap-2">
                  <Clock className="h-5 w-5" />
                  <span>
                    {listing.duration.value} {listing.duration.unit}
                  </span>
                </div>
              )}
              <div className="flex items-center gap-2">
                <Users className="h-5 w-5" />
                <span>Max {listing.maxGroupSize} guests</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="container mx-auto px-4 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main Content */}
          <div className="lg:col-span-2 space-y-8">
            {/* Description */}
            <div>
              <h2 className="text-2xl font-semibold text-neutral-900 mb-4">About</h2>
              <p className="text-neutral-700 whitespace-pre-line">{description}</p>
            </div>

            {/* Highlights */}
            {listing.highlights && listing.highlights.length > 0 && (
              <div>
                <h2 className="text-2xl font-semibold text-neutral-900 mb-4">{t('highlights')}</h2>
                <ul className="space-y-2">
                  {listing.highlights.map((highlight: any, index: number) => (
                    <li key={index} className="flex items-start gap-2">
                      <CheckCircle className="h-5 w-5 text-[#8BC34A] flex-shrink-0 mt-0.5" />
                      <span className="text-neutral-700">{tr(highlight)}</span>
                    </li>
                  ))}
                </ul>
              </div>
            )}

            {/* Included / Not Included */}
            <div className="grid md:grid-cols-2 gap-6">
              {listing.included && listing.included.length > 0 && (
                <div>
                  <h3 className="font-semibold text-neutral-900 mb-3">{t('included')}</h3>
                  <ul className="space-y-2">
                    {listing.included.map((item: any, index: number) => (
                      <li key={index} className="flex items-start gap-2 text-sm">
                        <CheckCircle className="h-4 w-4 text-green-500 flex-shrink-0 mt-0.5" />
                        <span className="text-neutral-600">{tr(item)}</span>
                      </li>
                    ))}
                  </ul>
                </div>
              )}

              {listing.notIncluded && listing.notIncluded.length > 0 && (
                <div>
                  <h3 className="font-semibold text-neutral-900 mb-3">{t('not_included')}</h3>
                  <ul className="space-y-2">
                    {listing.notIncluded.map((item: any, index: number) => (
                      <li key={index} className="flex items-start gap-2 text-sm">
                        <XCircle className="h-4 w-4 text-red-500 flex-shrink-0 mt-0.5" />
                        <span className="text-neutral-600">{tr(item)}</span>
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </div>

            {/* Requirements */}
            {listing.requirements && listing.requirements.length > 0 && (
              <div>
                <h3 className="font-semibold text-neutral-900 mb-3">Requirements</h3>
                <ul className="space-y-2">
                  {listing.requirements.map((req: any, index: number) => (
                    <li key={index} className="flex items-start gap-2 text-sm">
                      <AlertCircle className="h-4 w-4 text-[#f59e0b] flex-shrink-0 mt-0.5" />
                      <span className="text-neutral-600">{tr(req)}</span>
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </div>

          {/* Sidebar - Booking Panel (responsive: sticky on desktop, modal on mobile) */}
          <div className="lg:col-span-1 hidden lg:block">
            <BookingPanel
              pricing={listing.pricing}
              isOpen={showBookingFlow}
              onOpenChange={setShowBookingFlow}
            >
              <div className="space-y-6">
                <PriceDisplay
                  amount={listing.pricing?.basePrice || 0}
                  currency={listing.pricing?.currency || 'EUR'}
                  size="lg"
                  showFrom
                />

                {!showBookingFlow ? (
                  <>
                    <div className="space-y-3">
                      <Button
                        variant="primary"
                        size="lg"
                        className="w-full"
                        onClick={() => setShowBookingFlow(true)}
                      >
                        <Calendar className="h-5 w-5 mr-2" />
                        {t('check_availability')}
                      </Button>
                      <p className="text-xs text-neutral-500 text-center">
                        Free cancellation up to 24 hours before
                      </p>
                    </div>

                    <div className="pt-6 border-t border-neutral-200">
                      <div className="space-y-3 text-sm">
                        <div className="flex items-center gap-2 text-neutral-600">
                          <CheckCircle className="h-4 w-4 text-[#8BC34A]" />
                          <span>Instant confirmation</span>
                        </div>
                        <div className="flex items-center gap-2 text-neutral-600">
                          <CheckCircle className="h-4 w-4 text-[#8BC34A]" />
                          <span>Mobile ticket accepted</span>
                        </div>
                      </div>
                    </div>
                  </>
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
                    onClose={() => setShowBookingFlow(false)}
                    locale={locale}
                    t={t}
                    tAvail={tAvail}
                    tBooking={tBooking}
                    tCommon={tCommon}
                  />
                )}
              </div>
            </BookingPanel>
          </div>

          {/* Mobile: Floating button + Modal - only shown on mobile */}
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
                onClose={() => setShowBookingFlow(false)}
                locale={locale}
                t={t}
                tAvail={tAvail}
                tBooking={tBooking}
                tCommon={tCommon}
              />
            </BookingPanel>
          </div>
        </div>
      </div>
    </MainLayout>
  );
}
