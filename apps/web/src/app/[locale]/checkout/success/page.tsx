'use client';

import { useSearchParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { CheckCircle } from 'lucide-react';
import Link from 'next/link';
import type { Route } from 'next';
import { useEffect } from 'react';

/**
 * Checkout Success Page
 *
 * Displayed after a successful payment via Clictopay or other redirect-based gateway.
 * Shows confirmation details and provides navigation options.
 */
export default function CheckoutSuccessPage() {
  const searchParams = useSearchParams();
  const bookingNumber = searchParams.get('booking');
  const status = searchParams.get('status');
  const t = useTranslations('checkout');

  // Clear pending payment from session storage
  useEffect(() => {
    sessionStorage.removeItem('pending_payment');
  }, []);

  return (
    <div className="min-h-[60vh] flex items-center justify-center p-4">
      <div className="max-w-md w-full text-center">
        {/* Success Icon */}
        <div className="mb-6">
          <div className="w-20 h-20 bg-success/10 rounded-full flex items-center justify-center mx-auto">
            <CheckCircle className="w-12 h-12 text-success" />
          </div>
        </div>

        {/* Title */}
        <h1 className="text-2xl font-bold text-neutral-900 mb-2">{t('payment_successful')}</h1>

        {/* Booking Number */}
        {bookingNumber && (
          <p className="text-neutral-600 mb-6">
            {t('booking_confirmed', { number: bookingNumber })}
          </p>
        )}

        {/* Status Badge */}
        {status === 'confirmed' && (
          <div className="inline-flex items-center gap-2 px-4 py-2 bg-success/10 text-success rounded-full mb-8">
            <CheckCircle className="w-4 h-4" />
            <span className="text-sm font-medium">{t('status_confirmed')}</span>
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
