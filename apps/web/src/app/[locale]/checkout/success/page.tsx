'use client';

import { useSearchParams } from 'next/navigation';
import { useTranslations, useLocale } from 'next-intl';
import { CheckCircle, UserCheck } from 'lucide-react';
import Link from 'next/link';
import type { Route } from 'next';
import { useEffect, useMemo } from 'react';
import { Button } from '@go-adventure/ui';

/**
 * Checkout Success Page
 *
 * Displayed after a successful payment via Clictopay or other redirect-based gateway.
 * Supports both single booking and cart (multiple bookings) checkout flows.
 */
export default function CheckoutSuccessPage() {
  const searchParams = useSearchParams();
  const t = useTranslations('checkout');
  const tConfirm = useTranslations('cart.confirmation');
  const locale = useLocale();

  // Single booking params
  const singleBookingNumber = searchParams.get('booking');
  const status = searchParams.get('status');

  // Cart (multiple bookings) params
  const bookingIds = searchParams.get('bookings'); // comma-separated IDs
  const bookingNumbersParam = searchParams.get('booking_numbers'); // comma-separated numbers
  const checkoutType = searchParams.get('type'); // 'cart' for cart checkout

  // Parse multiple bookings if present
  const bookingNumbers = useMemo(() => {
    if (bookingNumbersParam) {
      return bookingNumbersParam.split(',').filter(Boolean);
    }
    if (singleBookingNumber) {
      return [singleBookingNumber];
    }
    return [];
  }, [bookingNumbersParam, singleBookingNumber]);

  const isCartCheckout = checkoutType === 'cart' || bookingNumbers.length > 1;

  // Build participant entry URL for cart checkout
  const participantsUrl = bookingIds
    ? `/${locale}/checkout/participants?bookings=${bookingIds}`
    : null;

  // Clear pending payment from session storage
  useEffect(() => {
    sessionStorage.removeItem('pending_payment');
  }, []);

  return (
    <div className="min-h-[60vh] flex items-center justify-center p-4">
      <div className="max-w-lg w-full text-center">
        {/* Success Icon */}
        <div className="mb-6">
          <div className="w-20 h-20 bg-success/10 rounded-full flex items-center justify-center mx-auto">
            <CheckCircle className="w-12 h-12 text-success" />
          </div>
        </div>

        {/* Title */}
        <h1 className="text-2xl font-bold text-neutral-900 mb-2">{t('payment_successful')}</h1>

        {/* Subtitle for cart checkout */}
        {isCartCheckout && (
          <p className="text-neutral-600 mb-6">
            {t('cart_bookings_confirmed', { count: bookingNumbers.length })}
          </p>
        )}

        {/* Status Badge */}
        {status === 'confirmed' && (
          <div className="inline-flex items-center gap-2 px-4 py-2 bg-success/10 text-success rounded-full mb-6">
            <CheckCircle className="w-4 h-4" />
            <span className="text-sm font-medium">{t('status_confirmed')}</span>
          </div>
        )}

        {/* Booking Numbers Section */}
        {bookingNumbers.length > 0 && (
          <div className="bg-white border-2 border-primary/30 rounded-xl p-6 mb-6 text-left">
            <h3 className="text-lg font-semibold text-neutral-900 mb-4 text-center">
              {isCartCheckout ? tConfirm('your_booking_numbers') : t('your_booking_number')}
            </h3>
            <div className="space-y-3">
              {bookingNumbers.map((number, index) => (
                <div
                  key={number}
                  className="flex items-center justify-between bg-neutral-50 rounded-lg p-4 border border-neutral-200"
                >
                  <div>
                    <p className="text-sm text-neutral-500 uppercase tracking-wide">
                      {isCartCheckout
                        ? `${tConfirm('booking_number')} ${index + 1}`
                        : tConfirm('booking_number')}
                    </p>
                  </div>
                  <div className="text-right">
                    <p className="text-xl font-bold text-primary font-mono">#{number}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Participant Names Entry Prompt (for cart checkout) */}
        {isCartCheckout && participantsUrl && (
          <div className="bg-amber-50 border border-amber-200 rounded-xl p-6 mb-6">
            <div className="flex items-start gap-4">
              <div className="flex-shrink-0 w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center">
                <UserCheck className="w-6 h-6 text-amber-600" />
              </div>
              <div className="flex-1 text-left">
                <h3 className="font-semibold text-neutral-900 mb-1">
                  {tConfirm('enter_participant_names')}
                </h3>
                <p className="text-sm text-neutral-600 mb-4">
                  {tConfirm('participant_names_description')}
                </p>
                <Link href={participantsUrl as Route}>
                  <Button
                    size="sm"
                    variant="outline"
                    className="border-amber-300 hover:bg-amber-100"
                  >
                    {tConfirm('enter_names_button')}
                  </Button>
                </Link>
              </div>
            </div>
          </div>
        )}

        {/* What's Next */}
        <div className="bg-neutral-50 rounded-lg p-4 mb-6 text-left">
          <h3 className="font-medium text-neutral-900 mb-2">{t('whats_next')}</h3>
          <ul className="text-sm text-neutral-600 space-y-2">
            <li>{t('confirmation_email_sent')}</li>
            <li>{t('check_booking_details')}</li>
          </ul>
        </div>

        {/* Action Buttons */}
        <div className="flex flex-col sm:flex-row gap-3 justify-center">
          <Link
            href={'/dashboard/bookings' as Route}
            className="inline-flex items-center justify-center px-6 py-3 bg-primary text-white font-medium rounded-lg hover:bg-primary-dark transition-colors"
          >
            {t('view_my_bookings')}
          </Link>
          <Link
            href={'/' as Route}
            className="inline-flex items-center justify-center px-6 py-3 bg-neutral-100 text-neutral-700 font-medium rounded-lg hover:bg-neutral-200 transition-colors"
          >
            {t('return_home')}
          </Link>
        </div>
      </div>
    </div>
  );
}
