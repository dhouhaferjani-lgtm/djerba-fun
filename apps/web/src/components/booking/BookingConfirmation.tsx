'use client';

import { useTranslations } from 'next-intl';
import { Link } from '@/i18n/navigation';
import type { Booking } from '@go-adventure/schemas';

interface BookingConfirmationProps {
  booking: Booking;
}

export function BookingConfirmation({ booking }: BookingConfirmationProps) {
  const t = useTranslations('booking.confirmation');

  // Get booking number (bookingNumber is primary, code is alias)
  const bookingNumber = booking.bookingNumber || booking.code || booking.id;

  // Get guest count (quantity is primary, guests is alias)
  const guestCount = booking.quantity || booking.guests || 1;

  // Get start date (startsAt from BookingResource, or from availabilitySlot relationship)
  const startDate =
    booking.startsAt || booking.availabilitySlot?.start || booking.confirmedAt || booking.createdAt;

  // Get traveler email (travelerInfo is API format, travelers is schema format)
  const travelerEmail = booking.travelerInfo?.email || booking.travelers?.[0]?.email;

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
    <div className="max-w-2xl mx-auto">
      <div className="text-center space-y-6">
        {/* Success Animation */}
        <div className="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full">
          <svg
            className="w-12 h-12 text-green-600"
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
              <p className="text-xl font-bold text-gray-900">
                {formatPrice(booking.totalAmount, booking.currency)}
              </p>
            </div>
          </div>
        </div>

        {/* Email Confirmation Notice */}
        {travelerEmail && (
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p className="text-sm text-blue-800">
              {t('email_sent')} <strong>{travelerEmail}</strong>
            </p>
          </div>
        )}

        {/* Action Buttons */}
        <div className="flex flex-col sm:flex-row gap-4 pt-4">
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
