'use client';

import { useState, useMemo } from 'react';
import Image from 'next/image';
import { useParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { format, addMonths } from 'date-fns';
import { MainLayout } from '@/components/templates/MainLayout';
import { useListing, useAvailability, useCreateHold } from '@/lib/api/hooks';
import { Button, Card } from '@go-adventure/ui';
import { RatingStars } from '@/components/molecules/RatingStars';
import { PriceDisplay } from '@/components/molecules/PriceDisplay';
import AvailabilityCalendar from '@/components/availability/AvailabilityCalendar';
import { BookingWizard } from '@/components/booking/BookingWizard';
import {
  MapPin,
  Clock,
  Users,
  Calendar,
  CheckCircle,
  XCircle,
  AlertCircle,
  Minus,
  Plus,
  X,
} from 'lucide-react';
import { resolveTranslation } from '@/lib/utils/translate';
// Using any for API response types due to schema/API mismatch
type ApiSlot = any;
type ApiHold = any;
type ApiListing = any;

export default function ListingDetailPage() {
  const params = useParams();
  const locale = params?.locale as string;
  const slug = params?.slug as string;
  const t = useTranslations('listing');
  const tCommon = useTranslations('common');
  const tAvail = useTranslations('availability');
  const tBooking = useTranslations('booking');

  // Booking flow state
  const [showBookingFlow, setShowBookingFlow] = useState(false);
  const [selectedDate, setSelectedDate] = useState<Date | undefined>();
  const [selectedSlot, setSelectedSlot] = useState<ApiSlot | undefined>();
  const [participants, setParticipants] = useState(1);
  const [hold, setHold] = useState<ApiHold | undefined>();

  const { data: listing, isLoading, error } = useListing(slug);

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
      (slot: ApiSlot) =>
        slot.start.startsWith(dateStr) && (slot.status === 'available' || slot.status === 'limited')
    );
  }, [selectedDate, availabilityData]);

  const handleDateSelect = (date: Date) => {
    setSelectedDate(date);
    setSelectedSlot(undefined);
  };

  const handleSlotSelect = (slot: ApiSlot) => {
    setSelectedSlot(slot);
  };

  const handleCreateHold = async () => {
    if (!selectedSlot) return;

    try {
      const response = await createHoldMutation.mutateAsync({
        slotId: selectedSlot.id,
        quantity: participants,
      } as any);
      setHold(response.data);
    } catch (err) {
      console.error('Failed to create hold:', err);
    }
  };

  const handleHoldExpired = () => {
    setHold(undefined);
    setSelectedSlot(undefined);
  };

  const resetBookingFlow = () => {
    setShowBookingFlow(false);
    setSelectedDate(undefined);
    setSelectedSlot(undefined);
    setHold(undefined);
    setParticipants(1);
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
  const maxParticipants = selectedSlot?.remainingCapacity || listing.maxGroupSize || 10;

  // If we have a hold, show the booking wizard
  if (hold && selectedSlot) {
    return (
      <MainLayout locale={locale}>
        <div className="container mx-auto px-4 py-8">
          <button
            onClick={resetBookingFlow}
            className="flex items-center gap-2 text-neutral-600 hover:text-neutral-900 mb-6"
          >
            <X className="h-5 w-5" />
            <span>Cancel booking</span>
          </button>
          <BookingWizard
            hold={hold}
            listing={listing as any}
            slot={selectedSlot}
            onExpired={handleHoldExpired}
          />
        </div>
      </MainLayout>
    );
  }

  return (
    <MainLayout locale={locale}>
      {/* Hero Image */}
      <div className="relative h-96 w-full bg-neutral-100">
        {mainImage && (
          <Image
            src={mainImage.url}
            alt={tr(mainImage.alt) || title}
            fill
            className="object-cover"
            priority
          />
        )}
      </div>

      <div className="container mx-auto px-4 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main Content */}
          <div className="lg:col-span-2 space-y-8">
            {/* Title and Rating */}
            <div>
              <h1 className="text-4xl font-bold text-neutral-900 mb-4">{title}</h1>
              {listing.rating && (
                <div className="flex items-center gap-2">
                  <RatingStars rating={listing.rating} showNumber />
                  <span className="text-sm text-neutral-500">
                    ({tCommon('reviews', { count: listing.reviewsCount || 0 })})
                  </span>
                </div>
              )}
            </div>

            {/* Quick Info */}
            <div className="flex flex-wrap gap-6 text-sm">
              {listing.serviceType === 'tour' && listing.duration && (
                <div className="flex items-center gap-2 text-neutral-600">
                  <Clock className="h-5 w-5" />
                  <span>
                    {listing.duration.value} {listing.duration.unit}
                  </span>
                </div>
              )}
              <div className="flex items-center gap-2 text-neutral-600">
                <Users className="h-5 w-5" />
                <span>Max {listing.maxGroupSize} guests</span>
              </div>
              <div className="flex items-center gap-2 text-neutral-600">
                <MapPin className="h-5 w-5" />
                <span>{listing.meetingPoint?.address || 'Tunisia'}</span>
              </div>
            </div>

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

          {/* Sidebar - Booking Card */}
          <div className="lg:col-span-1">
            <Card className="sticky top-20">
              <div className="p-6 space-y-6">
                <PriceDisplay
                  amount={(listing.pricing as any).basePrice || (listing.pricing as any).base}
                  currency={listing.pricing.currency}
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
                  <div className="space-y-6">
                    {/* Close button */}
                    <button
                      onClick={() => setShowBookingFlow(false)}
                      className="absolute top-4 right-4 p-1 text-neutral-400 hover:text-neutral-600"
                    >
                      <X className="h-5 w-5" />
                    </button>

                    {/* Calendar */}
                    <div>
                      <h3 className="font-semibold text-neutral-900 mb-4">
                        {tAvail('select_date')}
                      </h3>
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

                    {/* Time Slots */}
                    {selectedDate && slotsForSelectedDate.length > 0 && (
                      <div>
                        <h3 className="font-semibold text-neutral-900 mb-4">
                          {tAvail('select_time')}
                        </h3>
                        <div className="space-y-2">
                          {slotsForSelectedDate.map((slot: ApiSlot) => (
                            <button
                              key={slot.id}
                              onClick={() => handleSlotSelect(slot)}
                              className={`w-full p-3 rounded-lg border text-left transition-colors ${
                                selectedSlot?.id === slot.id
                                  ? 'border-primary bg-primary/5'
                                  : 'border-neutral-200 hover:border-neutral-300'
                              }`}
                            >
                              <div className="flex items-center justify-between">
                                <span className="font-medium">
                                  {format(new Date(slot.start), 'HH:mm')}
                                </span>
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
                      <p className="text-center text-neutral-500 py-4">
                        {tAvail('no_slots_available')}
                      </p>
                    )}

                    {/* Participants */}
                    {selectedSlot && (
                      <div>
                        <h3 className="font-semibold text-neutral-900 mb-4">
                          {tBooking('travelers')}
                        </h3>
                        <div className="flex items-center justify-between border rounded-lg p-3">
                          <span className="text-neutral-700">Guests</span>
                          <div className="flex items-center gap-3">
                            <button
                              onClick={() => setParticipants((p) => Math.max(1, p - 1))}
                              disabled={participants <= 1}
                              className="p-1 rounded-full border border-neutral-300 disabled:opacity-50"
                            >
                              <Minus className="h-4 w-4" />
                            </button>
                            <span className="w-8 text-center font-medium">{participants}</span>
                            <button
                              onClick={() =>
                                setParticipants((p) => Math.min(maxParticipants, p + 1))
                              }
                              disabled={participants >= maxParticipants}
                              className="p-1 rounded-full border border-neutral-300 disabled:opacity-50"
                            >
                              <Plus className="h-4 w-4" />
                            </button>
                          </div>
                        </div>
                      </div>
                    )}

                    {/* Total and Book Button */}
                    {selectedSlot && (
                      <div className="pt-4 border-t border-neutral-200">
                        <div className="flex items-center justify-between mb-4">
                          <span className="text-neutral-600">{tBooking('total')}</span>
                          <PriceDisplay
                            amount={
                              (selectedSlot.price ||
                                (listing.pricing as any).basePrice ||
                                (listing.pricing as any).base) * participants
                            }
                            currency={selectedSlot.currency || listing.pricing.currency}
                            size="lg"
                          />
                        </div>
                        <Button
                          variant="primary"
                          size="lg"
                          className="w-full"
                          onClick={handleCreateHold}
                          disabled={createHoldMutation.isPending}
                        >
                          {createHoldMutation.isPending ? tCommon('loading') : tCommon('book_now')}
                        </Button>
                        {createHoldMutation.isError && (
                          <p className="text-sm text-red-600 mt-2 text-center">
                            {tCommon('error')}
                          </p>
                        )}
                      </div>
                    )}
                  </div>
                )}
              </div>
            </Card>
          </div>
        </div>
      </div>
    </MainLayout>
  );
}
