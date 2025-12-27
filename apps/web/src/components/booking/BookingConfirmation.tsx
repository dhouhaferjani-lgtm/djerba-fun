'use client';

import { useTranslations } from 'next-intl';
import { Link } from '@/i18n/navigation';
import { useAuth } from '@/lib/contexts/AuthContext';
import type { Booking } from '@go-adventure/schemas';

interface BookingConfirmationProps {
  booking: Booking;
}

export function BookingConfirmation({ booking }: BookingConfirmationProps) {
  const t = useTranslations('booking.confirmation');
  const { user } = useAuth();

  // Get booking number (bookingNumber is primary, code is alias)
  const bookingNumber = booking.bookingNumber || booking.code || booking.id;

  // Get guest count (quantity is primary, guests is alias)
  const guestCount = booking.quantity || booking.guests || 1;

  // Get start date (startsAt from BookingResource, or from availabilitySlot relationship)
  const startDate =
    booking.startsAt || booking.availabilitySlot?.start || booking.confirmedAt || booking.createdAt;

  // Get traveler email (travelerInfo is API format, travelers is schema format)
  const travelerEmail =
    booking.billingContact?.email || booking.travelerInfo?.email || booking.travelers?.[0]?.email;

  // Determine participant names requirement
  const needsParticipantNames =
    booking.travelerDetailsStatus === 'pending' || booking.travelerDetailsStatus === 'partial';
  const participantNamesComplete = booking.travelerDetailsStatus === 'complete';

  // Check if immediate vs. flexible timing (from listing configuration)
  // In real app, this would come from booking.listing.travelerNamesTiming
  // For now, show urgent prompt if status is 'pending' (assumed immediate)
  const promptImmediately = booking.travelerDetailsStatus === 'pending';

  // Check if user has an account (for guest checkout CTA)
  const isGuestBooking = !booking.userId && !user;

  const formatDateTime = (datetime: string | undefined) => {
    if (!datetime) return 'Not available';
    try {
      const date = new Date(datetime);
      if (isNaN(date.getTime())) return 'Not available';
      return new Intl.DateTimeFormat('en-US', {
        dateStyle: 'full',
        timeStyle: 'short',
      }).format(date);
    } catch {
      return 'Not available';
    }
  };

  const formatPrice = (amount: number, currency: string) => {
    // Prices are stored as whole amounts (e.g., 65 = €65), not cents
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency,
    }).format(amount);
  };

  return (
    <div className="max-w-2xl mx-auto" data-testid="booking-confirmation">
      <div className="text-center space-y-6">
        {/* Success Animation */}
        <div className="inline-flex items-center justify-center w-20 h-20 bg-success-light rounded-full">
          <svg
            className="w-12 h-12 text-success"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
          </svg>
        </div>

        {/* Title */}
        <div>
          <h1 className="text-3xl font-bold text-gray-900 mb-2">{t('title')}</h1>
          <p className="text-lg text-gray-600">{t('subtitle')}</p>
        </div>

        {/* Booking Number */}
        <div className="bg-primary/5 border border-primary/20 rounded-lg p-6">
          <p className="text-sm text-gray-600 mb-1">{t('number')}</p>
          <p className="text-2xl font-bold text-primary">{bookingNumber}</p>
        </div>

        {/* Booking Summary */}
        <div className="bg-white border border-gray-200 rounded-lg p-6 text-left">
          <h2 className="font-semibold text-lg text-gray-900 mb-4">{t('booking_summary')}</h2>
          <div className="space-y-3 text-sm">
            <div>
              <span className="text-gray-600">{t('number')}:</span>
              <p className="font-medium text-gray-900">{bookingNumber}</p>
            </div>
            <div>
              <span className="text-gray-600">{t('activity')}:</span>
              <p className="font-medium text-gray-900">{formatDateTime(startDate)}</p>
            </div>
            <div>
              <span className="text-gray-600">{t('guests')}:</span>
              <p className="font-medium text-gray-900">{guestCount}</p>
            </div>
            <div className="pt-3 border-t">
              <span className="text-gray-600">{t('total_paid')}:</span>
              <p className="text-xl font-bold text-gray-900" data-testid="confirmation-total">
                {formatPrice(booking.totalAmount, booking.currency)}
              </p>
            </div>
          </div>
        </div>

        {/* Email Confirmation Notice */}
        {travelerEmail && (
          <div className="bg-success-light border border-success/20 rounded-lg p-4">
            <p className="text-sm text-success-dark">
              {t('email_sent')} <strong>{travelerEmail}</strong>
            </p>
          </div>
        )}

        {/* URGENT Participant Names Prompt - immediate requirement */}
        {needsParticipantNames && promptImmediately && (
          <div className="bg-warning-light border-2 border-warning rounded-lg p-6 text-left shadow-md">
            <div className="flex items-start gap-3">
              <div className="flex-shrink-0 w-12 h-12 bg-warning-light rounded-full flex items-center justify-center">
                <svg
                  className="w-6 h-6 text-warning"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                  />
                </svg>
              </div>
              <div className="flex-1">
                <h3 className="font-bold text-warning-dark mb-2 text-lg">
                  {t('names_required_title') || '⚠️ Participant Names Required'}
                </h3>
                <p className="text-sm text-warning-dark mb-4">
                  {t('names_required_message') ||
                    'This activity requires participant names before departure. Please provide names now to ensure a smooth check-in process.'}
                </p>
                <Link
                  href={`/dashboard/bookings/${booking.id}/participants`}
                  className="inline-flex items-center px-5 py-3 bg-warning text-white rounded-lg font-semibold text-sm hover:bg-warning-dark transition-colors shadow-sm"
                >
                  {t('provide_names_now') || 'Provide Names Now'}
                  <svg
                    className="w-5 h-5 ml-2"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M9 5l7 7-7 7"
                    />
                  </svg>
                </Link>
              </div>
            </div>
          </div>
        )}

        {/* FLEXIBLE Participant Names Prompt - optional/before activity */}
        {needsParticipantNames && !promptImmediately && (
          <div className="bg-success-light border border-success/20 rounded-lg p-6 text-left">
            <div className="flex items-start gap-3">
              <div className="flex-shrink-0 w-10 h-10 bg-success-light rounded-full flex items-center justify-center">
                <svg
                  className="w-5 h-5 text-success"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                  />
                </svg>
              </div>
              <div className="flex-1">
                <h3 className="font-semibold text-success-dark mb-1">
                  {t('names_optional_title') || 'Participant Names (Optional)'}
                </h3>
                <p className="text-sm text-success-dark mb-3">
                  {t('names_optional_message') ||
                    'You can provide participant names now or later before your activity date.'}
                </p>
                <Link
                  href={`/dashboard/bookings/${booking.id}/participants`}
                  className="inline-flex items-center text-success hover:text-success-dark font-medium text-sm hover:underline"
                >
                  {t('add_names') || 'Add Names'} →
                </Link>
              </div>
            </div>
          </div>
        )}

        {/* Account Creation CTA - for guest bookings only */}
        {isGuestBooking && (
          <div className="bg-gradient-to-r from-primary/10 to-primary-light/10 border border-primary/30 rounded-lg p-6 text-left">
            <div className="flex items-start gap-4">
              <div className="flex-shrink-0 w-12 h-12 bg-primary/20 rounded-full flex items-center justify-center">
                <svg
                  className="w-6 h-6 text-primary"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
              </div>
              <div className="flex-1">
                <h3 className="font-bold text-gray-900 mb-2 text-lg">
                  {t('create_account_title') || '✨ Create Your Account'}
                </h3>
                <p className="text-sm text-gray-700 mb-3">
                  {t('create_account_subtitle') ||
                    'Track all your bookings, get faster checkout, and receive exclusive offers. No password needed!'}
                </p>
                <ul className="space-y-2 mb-4 text-sm text-gray-700">
                  <li className="flex items-center gap-2">
                    <svg className="w-4 h-4 text-success" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fillRule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clipRule="evenodd"
                      />
                    </svg>
                    <span>View all bookings anytime</span>
                  </li>
                  <li className="flex items-center gap-2">
                    <svg className="w-4 h-4 text-success" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fillRule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clipRule="evenodd"
                      />
                    </svg>
                    <span>One-click future checkouts</span>
                  </li>
                  <li className="flex items-center gap-2">
                    <svg className="w-4 h-4 text-success" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fillRule="evenodd"
                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                        clipRule="evenodd"
                      />
                    </svg>
                    <span>Save favorite activities</span>
                  </li>
                </ul>
                <Link
                  href={`/auth/register-quick?email=${encodeURIComponent(travelerEmail || '')}&bookingId=${booking.id}`}
                  className="inline-flex items-center px-5 py-3 bg-primary text-white rounded-lg font-semibold text-sm hover:bg-primary/90 transition-colors shadow-sm"
                >
                  {t('create_free_account') || 'Create Free Account'} →
                </Link>
                <p className="text-xs text-gray-600 mt-3">
                  {t('passwordless_note') || 'No password needed - magic link verification'}
                </p>
              </div>
            </div>
          </div>
        )}

        {/* Action Buttons */}
        <div className="flex flex-col sm:flex-row gap-4 pt-4">
          {guestCount > 1 ? (
            <>
              <Link
                href={`/dashboard/bookings/${booking.id}/participants`}
                className="flex-1 px-6 py-3 bg-primary text-white rounded-lg font-medium text-center hover:bg-primary/90 transition-colors"
              >
                {t('enter_names') || 'Enter Participant Names'}
              </Link>
              <Link
                href={`/dashboard/bookings/${booking.id}`}
                className="flex-1 px-6 py-3 border border-gray-300 rounded-lg font-medium text-center text-gray-700 hover:bg-gray-50 transition-colors"
              >
                {t('do_later') || 'Do This Later'}
              </Link>
            </>
          ) : (
            <>
              <Link
                href={`/dashboard/bookings/${booking.id}`}
                className="flex-1 px-6 py-3 bg-primary text-white rounded-lg font-medium text-center hover:bg-primary/90 transition-colors"
              >
                {t('view_booking')}
              </Link>
              <Link
                href="/"
                className="flex-1 px-6 py-3 border border-gray-300 rounded-lg font-medium text-center text-gray-700 hover:bg-gray-50 transition-colors"
              >
                {t('back_home')}
              </Link>
            </>
          )}
        </div>

        {/* Share Options */}
        <div className="pt-6 border-t">
          <p className="text-sm text-gray-600 mb-3">{t('share_experience')}</p>
          <div className="flex justify-center gap-3">
            <button
              type="button"
              className="p-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              aria-label="Share on Facebook"
            >
              <svg className="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                <path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z" />
              </svg>
            </button>
            <button
              type="button"
              className="p-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              aria-label="Share on Twitter"
            >
              <svg className="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                <path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z" />
              </svg>
            </button>
            <button
              type="button"
              className="p-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              aria-label="Share via Email"
            >
              <svg
                className="w-5 h-5 text-gray-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
