'use client';

import { useState, useEffect, useCallback } from 'react';
import { useSearchParams, useRouter } from 'next/navigation';
import { useTranslations, useLocale } from 'next-intl';
import { useQueries } from '@tanstack/react-query';
import { bookingsApi } from '@/lib/api/client';
import { useUpdateParticipants, useBulkApplyParticipants } from '@/lib/api/hooks';
import { ActivityAccordion, type ParticipantData } from '@/components/booking/ActivityAccordion';
import { Button } from '@go-adventure/ui';
import { Loader2, CheckCircle, AlertTriangle, Users, Copy } from 'lucide-react';
import { getGuestSessionId } from '@/lib/utils/session';
import type { Booking } from '@go-adventure/schemas';

/**
 * Cart Participants Entry Page - Accordion View
 *
 * Simplified UX that goes directly to accordion view.
 * Shows ALL activities on ONE page with collapsible accordions.
 * Key features:
 * - All activities visible at once
 * - Per-activity save (no data loss)
 * - "Copy to All" feature after first activity is saved
 * - Progress indicator per activity and overall
 * - Skip for Now option with email reminder
 */
export default function CartParticipantsPage() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const locale = useLocale();
  const t = useTranslations('booking.participants');

  const [expandedBooking, setExpandedBooking] = useState<string | null>(null);
  const [savedBookings, setSavedBookings] = useState<Record<string, boolean>>({});
  const [savingBooking, setSavingBooking] = useState<string | null>(null);
  const [formData, setFormData] = useState<Record<string, ParticipantData[]>>({});
  const [showSkipConfirm, setShowSkipConfirm] = useState(false);
  const [firstSavedData, setFirstSavedData] = useState<ParticipantData[] | null>(null);
  const [isCopying, setIsCopying] = useState(false);

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

  // Initialize form data for each booking when loaded
  // Uses functional update to handle async booking loads correctly
  useEffect(() => {
    if (bookings.length === 0) return;

    setFormData((prev) => {
      const updated = { ...prev };
      let hasChanges = false;

      bookings.forEach((booking) => {
        // Skip if this booking is already initialized
        if (updated[booking.id]) return;

        hasChanges = true;
        const existingParticipants = (booking.participants || []) as Array<{
          id: string;
          firstName?: string;
          lastName?: string;
          first_name?: string;
          last_name?: string;
          email?: string;
          phone?: string;
        }>;
        const quantity = booking.quantity || existingParticipants.length || 1;

        if (existingParticipants.length > 0) {
          updated[booking.id] = existingParticipants.map((p) => ({
            id: p.id,
            firstName: p.firstName || p.first_name || '',
            lastName: p.lastName || p.last_name || '',
            email: p.email || '',
            phone: p.phone || '',
          }));
        } else {
          updated[booking.id] = Array.from({ length: quantity }, (_, i) => ({
            id: `temp-${booking.id}-${i}`,
            firstName: '',
            lastName: '',
            email: '',
            phone: '',
          }));
        }
      });

      return hasChanges ? updated : prev;
    });

    // Auto-expand first booking if none expanded
    if (!expandedBooking && bookings[0]) {
      setExpandedBooking(bookings[0].id);
    }
  }, [bookings, expandedBooking]);

  // Calculate saved count for "Copy to All" visibility
  const savedCount = Object.values(savedBookings).filter(Boolean).length;

  // Handle individual activity save
  const handleActivitySave = useCallback(
    async (bookingId: string, participants: ParticipantData[]) => {
      const booking = bookings.find((b) => b.id === bookingId);
      if (!booking) return;

      setSavingBooking(bookingId);

      try {
        // Transform data to API format
        const apiParticipants = participants.map((p) => ({
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

        // Mark this booking as saved
        setSavedBookings((prev) => ({ ...prev, [bookingId]: true }));

        // Store first saved data for "Copy to All" feature
        if (savedCount === 0) {
          setFirstSavedData(participants);
        }

        // Auto-expand next unsaved booking
        const currentSavedBookings = { ...savedBookings, [bookingId]: true };
        const nextUnsaved = bookings.find((b) => b.id !== bookingId && !currentSavedBookings[b.id]);
        if (nextUnsaved) {
          setExpandedBooking(nextUnsaved.id);
        }
      } catch (error) {
        console.error('Update participants failed:', error);
        // TODO: Show error toast
      } finally {
        setSavingBooking(null);
      }
    },
    [bookings, savedBookings, savedCount, updateParticipantsMutation]
  );

  // Handle "Copy to All" - copies first saved activity's names to all remaining activities
  const handleCopyToAll = async () => {
    if (!firstSavedData) return;

    setIsCopying(true);

    try {
      // Get all unsaved bookings
      const unsavedBookingIds = bookings.filter((b) => !savedBookings[b.id]).map((b) => b.id);

      if (unsavedBookingIds.length === 0) return;

      // Call bulk apply API with first saved data
      await bulkApplyMutation.mutateAsync({
        bookingIds: unsavedBookingIds,
        participants: firstSavedData.map((p) => ({
          first_name: p.firstName,
          last_name: p.lastName,
          email: p.email || null,
          phone: p.phone || null,
        })),
      });

      // Redirect to vouchers page
      router.push(`/${locale}/checkout/vouchers?bookings=${bookingIds.join(',')}`);
    } catch (error) {
      console.error('Copy to all failed:', error);
      // TODO: Show error toast
    } finally {
      setIsCopying(false);
    }
  };

  // Handle form data change
  const handleFormChange = useCallback((bookingId: string, data: ParticipantData[]) => {
    setFormData((prev) => ({ ...prev, [bookingId]: data }));
    // Clear saved status if data changes
    setSavedBookings((prev) => ({ ...prev, [bookingId]: false }));
  }, []);

  // Handle Save All & View Vouchers
  const handleSaveAllAndView = async () => {
    // Save any unsaved bookings that are complete
    const unsavedBookings = bookings.filter((b) => !savedBookings[b.id]);

    for (const booking of unsavedBookings) {
      const data = formData[booking.id];
      const isComplete = data?.every((p) => p.firstName.trim() && p.lastName.trim());
      if (isComplete) {
        await handleActivitySave(booking.id, data);
      }
    }

    // Redirect to vouchers page
    const bookingIdsJoined = bookingIds.join(',');
    router.push(`/${locale}/checkout/vouchers?bookings=${bookingIdsJoined}`);
  };

  // Handle Skip for Now
  const handleSkipForNow = () => {
    setShowSkipConfirm(true);
  };

  const confirmSkip = () => {
    // Redirect to dashboard
    router.push(`/${locale}/dashboard/bookings`);
    // TODO: Trigger email reminder via API
  };

  // Calculate overall progress
  const totalParticipants = bookings.reduce((sum, b) => sum + (b.quantity || 1), 0);
  const completedParticipants = Object.values(formData).reduce((sum, participants) => {
    return sum + (participants?.filter((p) => p.firstName.trim() && p.lastName.trim()).length || 0);
  }, 0);
  const allSaved = savedCount === bookings.length;
  const progressPercent =
    totalParticipants > 0 ? Math.round((completedParticipants / totalParticipants) * 100) : 0;

  // Determine if "Copy to All" should be shown
  const showCopyToAll = savedCount > 0 && savedCount < bookings.length && firstSavedData;

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

  // Accordion view (direct - no mode selection)
  return (
    <div className="max-w-3xl mx-auto px-4 py-8">
      {/* Header */}
      <div className="text-center mb-8">
        <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/10 text-primary mb-4">
          <Users className="w-8 h-8" />
        </div>
        <h1 className="text-2xl font-bold text-neutral-900 mb-2">
          {t('enter_participant_names') || 'Enter Participant Names'}
        </h1>
        <p className="text-neutral-600">
          {t('activities_participants_count', {
            activities: bookings.length,
            participants: totalParticipants,
          }) || `${bookings.length} activities • ${totalParticipants} participants total`}
        </p>
      </div>

      {/* "Copy to All" banner - shows after first activity is saved */}
      {showCopyToAll && (
        <div className="bg-primary/5 border border-primary/20 rounded-xl p-4 mb-6">
          <div className="flex items-center justify-between gap-4">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center flex-shrink-0">
                <Copy className="w-5 h-5 text-primary" />
              </div>
              <div>
                <p className="font-medium text-neutral-900">
                  {t('same_participants_question') || 'Same participants for all activities?'}
                </p>
                <p className="text-sm text-neutral-600">
                  {t('copy_to_remaining', { count: bookings.length - savedCount }) ||
                    `Copy names to ${bookings.length - savedCount} remaining activities`}
                </p>
              </div>
            </div>
            <Button
              onClick={handleCopyToAll}
              variant="outline"
              size="sm"
              disabled={isCopying}
              className="flex-shrink-0"
            >
              {isCopying ? (
                <>
                  <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                  {t('copying') || 'Copying...'}
                </>
              ) : (
                <>
                  <Copy className="w-4 h-4 mr-2" />
                  {t('copy_to_all') || 'Copy to All'}
                </>
              )}
            </Button>
          </div>
        </div>
      )}

      {/* Activity accordions */}
      <div className="space-y-4 mb-8">
        {bookings.map((booking) => (
          <ActivityAccordion
            key={booking.id}
            booking={booking}
            isExpanded={expandedBooking === booking.id}
            onToggle={() => setExpandedBooking(expandedBooking === booking.id ? null : booking.id)}
            isSaved={savedBookings[booking.id] || false}
            isSaving={savingBooking === booking.id}
            onSave={(data) => handleActivitySave(booking.id, data)}
            formData={formData[booking.id] || []}
            onFormChange={(data) => handleFormChange(booking.id, data)}
          />
        ))}
      </div>

      {/* Overall progress bar */}
      <div className="bg-neutral-50 rounded-xl p-4 mb-6">
        <div className="flex items-center justify-between mb-2">
          <p className="text-sm font-medium text-neutral-700">
            {t('overall_progress') || 'Overall Progress'}
          </p>
          <p className="text-sm text-neutral-600">
            {completedParticipants}/{totalParticipants} {t('participants') || 'participants'} (
            {progressPercent}%)
          </p>
        </div>
        <div className="h-2 bg-neutral-200 rounded-full overflow-hidden">
          <div
            className="h-full bg-primary rounded-full transition-all duration-300"
            style={{ width: `${progressPercent}%` }}
          />
        </div>
        <p className="text-xs text-neutral-500 mt-2">
          {savedCount}/{bookings.length} {t('activities_saved') || 'activities saved'}
        </p>
      </div>

      {/* Action buttons */}
      <div className="flex flex-col sm:flex-row gap-4">
        <Button
          onClick={handleSaveAllAndView}
          className={`flex-1 ${allSaved ? 'animate-pulse-glow' : ''}`}
          disabled={completedParticipants === 0}
        >
          {allSaved ? (
            <>
              <CheckCircle className="w-4 h-4 mr-2" />
              {t('view_vouchers') || 'View Vouchers'}
            </>
          ) : (
            t('save_all_view_vouchers') || 'Save All & View Vouchers'
          )}
        </Button>
        <Button variant="outline" onClick={handleSkipForNow} className="flex-1 sm:flex-none">
          {t('skip_for_now') || 'Skip for Now'}
        </Button>
      </div>

      {/* Skip confirmation modal */}
      {showSkipConfirm && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl p-6 max-w-md w-full shadow-xl">
            <div className="text-center">
              <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-amber-100 text-amber-600 mb-4">
                <AlertTriangle className="w-6 h-6" />
              </div>
              <h3 className="text-lg font-semibold text-neutral-900 mb-2">
                {t('skip_confirm_title') || 'Skip for Now?'}
              </h3>
              <p className="text-neutral-600 mb-6">
                {t('skip_confirm_message') ||
                  "You can complete participant names later from your dashboard. We'll send you an email reminder. Note: Vouchers require completed names to download."}
              </p>
              <div className="flex gap-3">
                <Button
                  variant="outline"
                  onClick={() => setShowSkipConfirm(false)}
                  className="flex-1"
                >
                  {t('go_back') || 'Go Back'}
                </Button>
                <Button onClick={confirmSkip} className="flex-1">
                  {t('continue_to_dashboard') || 'Continue to Dashboard'}
                </Button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
