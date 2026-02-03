'use client';

import { useState, useEffect } from 'react';
import { useSearchParams, useRouter } from 'next/navigation';
import { useTranslations, useLocale } from 'next-intl';
import { useQueries } from '@tanstack/react-query';
import { bookingsApi } from '@/lib/api/client';
import { useUpdateParticipants, useBulkApplyParticipants } from '@/lib/api/hooks';
import { ParticipantModeSelector } from '@/components/booking/ParticipantModeSelector';
import { BulkParticipantsForm } from '@/components/booking/BulkParticipantsForm';
import { ParticipantsForm } from '@/components/booking/ParticipantsForm';
import { Button } from '@go-adventure/ui';
import { ChevronLeft, ChevronRight, CheckCircle, Loader2 } from 'lucide-react';
import { getGuestSessionId } from '@/lib/utils/session';
import type { Booking } from '@go-adventure/schemas';

type Mode = 'select' | 'same' | 'different';

/**
 * Cart Participants Entry Page
 *
 * Handles participant name collection for multiple bookings from cart checkout.
 * Allows users to either:
 * - Enter names once and apply to all bookings (same for all)
 * - Enter names separately for each booking (different per tour)
 */
export default function CartParticipantsPage() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const locale = useLocale();
  const t = useTranslations('booking.participants');

  const [mode, setMode] = useState<Mode>('select');
  const [currentBookingIndex, setCurrentBookingIndex] = useState(0);
  const [completedBookings, setCompletedBookings] = useState<string[]>([]);

  // Get booking IDs from URL params
  const bookingIdsParam = searchParams.get('bookings');
  const bookingIds = bookingIdsParam ? bookingIdsParam.split(',').filter(Boolean) : [];

  // Determine if we should use guest access
  const token = typeof window !== 'undefined' ? localStorage.getItem('auth_token') : null;
  const useGuestAccess = !token;

  // Fetch all bookings
  const bookingQueries = useQueries({
    queries: bookingIds.map((id) => ({
      queryKey: ['bookings', id, useGuestAccess ? 'guest' : 'auth'],
      queryFn: async () => {
        if (useGuestAccess) {
          const sessionId = getGuestSessionId();
          if (!sessionId) throw new Error('No session ID');
          const response = await bookingsApi.getByIdGuest(id, sessionId);
          return response.data;
        } else {
          const response = await bookingsApi.getById(id);
          return response.data;
        }
      },
    })),
  });

  // Extract bookings from queries
  const bookings = bookingQueries.map((q) => q.data).filter((b): b is Booking => b !== undefined);

  const isLoading = bookingQueries.some((q) => q.isLoading);
  const hasError = bookingQueries.some((q) => q.isError);

  // Mutations
  const bulkApplyMutation = useBulkApplyParticipants(useGuestAccess);
  const updateParticipantsMutation = useUpdateParticipants(useGuestAccess);

  // Handle mode selection
  const handleModeSelect = (selectedMode: 'same' | 'different') => {
    setMode(selectedMode);
  };

  // Handle bulk apply submission
  const handleBulkSubmit = async (
    participants: Array<{
      firstName: string;
      lastName: string;
      email?: string;
      phone?: string;
    }>
  ) => {
    try {
      // Convert from form format (firstName) to API format (first_name)
      const apiParticipants = participants.map((p) => ({
        first_name: p.firstName,
        last_name: p.lastName,
        email: p.email || null,
        phone: p.phone || null,
      }));

      await bulkApplyMutation.mutateAsync({
        bookingIds,
        participants: apiParticipants,
      });
      // Success - redirect to dashboard
      router.push(`/${locale}/dashboard/bookings`);
    } catch (error) {
      console.error('Bulk apply failed:', error);
      // TODO: Show error toast
    }
  };

  // Handle individual booking submission
  const handleIndividualSubmit = async (data: {
    participants: Array<{
      id: string;
      firstName: string;
      lastName: string;
      email?: string;
      phone?: string;
    }>;
  }) => {
    const booking = bookings[currentBookingIndex];
    if (!booking) return;

    try {
      // Transform data to API format
      const apiParticipants = data.participants.map((p) => ({
        id: p.id,
        first_name: p.firstName,
        last_name: p.lastName,
        email: p.email || null,
        phone: p.phone || null,
      }));

      await updateParticipantsMutation.mutateAsync({
        bookingId: booking.id,
        participants: apiParticipants as any,
      });

      // Mark this booking as completed
      setCompletedBookings((prev) => [...prev, booking.id]);

      // Move to next booking or finish
      if (currentBookingIndex < bookings.length - 1) {
        setCurrentBookingIndex((i) => i + 1);
      } else {
        // All done - redirect to dashboard
        router.push(`/${locale}/dashboard/bookings`);
      }
    } catch (error) {
      console.error('Update participants failed:', error);
      // TODO: Show error toast
    }
  };

  // Loading state
  if (isLoading) {
    return (
      <div className="min-h-[60vh] flex items-center justify-center">
        <div className="text-center">
          <Loader2 className="w-12 h-12 text-primary animate-spin mx-auto mb-4" />
          <p className="text-neutral-600">{t('loading_bookings') || 'Loading bookings...'}</p>
        </div>
      </div>
    );
  }

  // Error state
  if (hasError || bookings.length === 0) {
    return (
      <div className="min-h-[60vh] flex items-center justify-center p-4">
        <div className="text-center max-w-md">
          <h2 className="text-xl font-bold text-neutral-900 mb-2">
            {t('error_loading') || 'Error Loading Bookings'}
          </h2>
          <p className="text-neutral-600 mb-6">
            {t('error_loading_message') ||
              "We couldn't load your booking details. Please try again."}
          </p>
          <Button onClick={() => router.push(`/${locale}/dashboard/bookings`)}>
            {t('go_to_bookings') || 'Go to My Bookings'}
          </Button>
        </div>
      </div>
    );
  }

  // Mode selection view
  if (mode === 'select') {
    return (
      <div className="max-w-2xl mx-auto px-4 py-12">
        <ParticipantModeSelector bookings={bookings} onModeSelect={handleModeSelect} />
      </div>
    );
  }

  // Same for all - bulk entry view
  if (mode === 'same') {
    return (
      <div className="max-w-2xl mx-auto px-4 py-12">
        <BulkParticipantsForm
          bookings={bookings}
          onSubmit={handleBulkSubmit}
          isLoading={bulkApplyMutation.isPending}
        />
      </div>
    );
  }

  // Different per tour - individual entry view
  if (mode === 'different') {
    const currentBooking = bookings[currentBookingIndex];

    if (!currentBooking) {
      return (
        <div className="min-h-[60vh] flex items-center justify-center">
          <div className="text-center">
            <CheckCircle className="w-16 h-16 text-success mx-auto mb-4" />
            <h2 className="text-xl font-bold text-neutral-900 mb-2">
              {t('all_complete') || 'All participants entered!'}
            </h2>
            <Button onClick={() => router.push(`/${locale}/dashboard/bookings`)}>
              {t('go_to_bookings') || 'Go to My Bookings'}
            </Button>
          </div>
        </div>
      );
    }

    return (
      <div className="max-w-2xl mx-auto px-4 py-8">
        {/* Progress indicator */}
        <div className="mb-8">
          <div className="flex items-center justify-between mb-2">
            <h2 className="text-lg font-semibold text-neutral-900">
              {t('booking_progress', {
                current: currentBookingIndex + 1,
                total: bookings.length,
              }) || `Booking ${currentBookingIndex + 1} of ${bookings.length}`}
            </h2>
            <span className="text-sm text-neutral-500">#{currentBooking.bookingNumber}</span>
          </div>

          {/* Progress dots */}
          <div className="flex items-center gap-2">
            {bookings.map((booking, index) => (
              <div
                key={booking.id}
                className={`h-2 flex-1 rounded-full ${
                  index < currentBookingIndex
                    ? 'bg-success'
                    : index === currentBookingIndex
                      ? 'bg-primary'
                      : 'bg-neutral-200'
                }`}
              />
            ))}
          </div>
        </div>

        {/* Booking info header */}
        <div className="bg-neutral-50 rounded-lg p-4 mb-6">
          <p className="text-sm text-neutral-500">
            {t('entering_names_for') || 'Entering participant names for:'}
          </p>
          <p className="font-medium text-neutral-900">
            {(() => {
              const listing = currentBooking.listing as
                | { title?: string | Record<string, string> }
                | undefined;
              if (typeof listing === 'object' && listing?.title) {
                if (typeof listing.title === 'object') {
                  return listing.title[locale] || Object.values(listing.title)[0];
                }
                return listing.title;
              }
              return 'Booking';
            })()}
          </p>
          <p className="text-sm text-neutral-600">
            {currentBooking.quantity || 1} {t('participants') || 'participants'}
          </p>
        </div>

        {/* Participants form */}
        <ParticipantsForm
          booking={currentBooking}
          onSubmit={handleIndividualSubmit}
          isLoading={updateParticipantsMutation.isPending}
        />

        {/* Navigation */}
        {currentBookingIndex > 0 && (
          <div className="mt-6 pt-6 border-t">
            <Button
              variant="ghost"
              onClick={() => setCurrentBookingIndex((i) => Math.max(0, i - 1))}
            >
              <ChevronLeft className="w-4 h-4 mr-2" />
              {t('previous_booking') || 'Previous Booking'}
            </Button>
          </div>
        )}
      </div>
    );
  }

  return null;
}
