'use client';

import { useSearchParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { XCircle, RefreshCw, HelpCircle } from 'lucide-react';
import Link from 'next/link';
import type { Route } from 'next';
import { useEffect } from 'react';

/**
 * Checkout Failure Page
 *
 * Displayed when a payment fails via Clictopay or other redirect-based gateway.
 * Shows error information and provides options to retry or get help.
 */
export default function CheckoutFailurePage() {
  const searchParams = useSearchParams();
  const reason = searchParams.get('reason');
  const bookingNumber = searchParams.get('booking');
  const intentId = searchParams.get('intent');
  const t = useTranslations('checkout');

  // Clear pending payment from session storage
  useEffect(() => {
    sessionStorage.removeItem('pending_payment');
  }, []);

  // Map reason codes to user-friendly messages
  const getErrorMessage = () => {
    switch (reason) {
      case 'payment_not_completed':
        return t('error_payment_not_completed');
      case 'payment_declined':
        return t('error_payment_declined');
      case 'payment_reversed':
        return t('error_payment_reversed');
      case 'gateway_error':
        return t('error_gateway_error');
      case 'verification_error':
        return t('error_verification_error');
      case 'processing_error':
        return t('error_processing_error');
      case 'payment_failed':
      default:
        return t('error_payment_failed');
    }
  };

  return (
    <div className="min-h-[60vh] flex items-center justify-center p-4">
      <div className="max-w-md w-full text-center">
        {/* Error Icon */}
        <div className="mb-6">
          <div className="w-20 h-20 bg-error/10 rounded-full flex items-center justify-center mx-auto">
            <XCircle className="w-12 h-12 text-error" />
          </div>
        </div>

        {/* Title */}
        <h1 className="text-2xl font-bold text-neutral-900 mb-2">{t('payment_failed')}</h1>

        {/* Error Message */}
        <p className="text-neutral-600 mb-6">{getErrorMessage()}</p>

        {/* Booking Reference */}
        {bookingNumber && (
          <div className="bg-neutral-50 rounded-lg p-4 mb-6">
            <p className="text-sm text-neutral-500">{t('booking_reference')}</p>
            <p className="font-mono font-medium text-neutral-900">{bookingNumber}</p>
          </div>
        )}

        {/* Suggestions */}
        <div className="bg-warning-light border border-warning/20 rounded-lg p-4 mb-6 text-left">
          <h3 className="font-medium text-warning-dark mb-2 flex items-center gap-2">
            <HelpCircle className="w-4 h-4" />
            {t('what_to_do')}
          </h3>
          <ul className="text-sm text-warning-dark space-y-2">
            <li>{t('suggestion_check_card')}</li>
            <li>{t('suggestion_try_again')}</li>
            <li>{t('suggestion_contact_bank')}</li>
          </ul>
        </div>

        {/* Action Buttons */}
        <div className="flex flex-col sm:flex-row gap-3 justify-center">
          <Link
            href={'/' as Route}
            className="inline-flex items-center justify-center gap-2 px-6 py-3 bg-primary text-white font-medium rounded-lg hover:bg-primary-dark transition-colors"
          >
            <RefreshCw className="w-4 h-4" />
            {t('try_again')}
          </Link>
          <Link
            href={'/contact' as Route}
            className="inline-flex items-center justify-center px-6 py-3 bg-neutral-100 text-neutral-700 font-medium rounded-lg hover:bg-neutral-200 transition-colors"
          >
            {t('contact_support')}
          </Link>
        </div>

        {/* Return Home Link */}
        <div className="mt-6">
          <Link
            href={'/' as Route}
            className="text-sm text-neutral-500 hover:text-neutral-700 underline"
          >
            {t('return_home')}
          </Link>
        </div>
      </div>
    </div>
  );
}
