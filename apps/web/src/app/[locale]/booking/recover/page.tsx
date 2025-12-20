'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { Link } from '@/i18n/navigation';
import { magicLinksApi } from '@/lib/api/client';

export default function BookingRecoverPage() {
  const t = useTranslations('booking');
  const tCommon = useTranslations('common');

  const [email, setEmail] = useState('');
  const [bookingNumber, setBookingNumber] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [success, setSuccess] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    setError(null);

    try {
      await magicLinksApi.resendMagicLink(email, bookingNumber);
      setSuccess(true);
    } catch (err: unknown) {
      const apiError = err as { status?: number; message?: string };
      if (apiError.status === 429) {
        setError('Too many requests. Please try again later.');
      } else {
        // Always show success to prevent email enumeration
        setSuccess(true);
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  if (success) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
        <div className="max-w-md w-full">
          <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
            <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg
                className="w-8 h-8 text-green-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M5 13l4 4L19 7"
                />
              </svg>
            </div>
            <h2 className="text-xl font-bold text-gray-900 mb-2">
              {t('recovery_email_sent') || 'Check Your Email'}
            </h2>
            <p className="text-gray-600 mb-6">
              {t('recovery_email_sent_description') ||
                'If a booking exists with that email and booking number, we have sent you a new secure link to access it.'}
            </p>
            <div className="space-y-3">
              <button
                onClick={() => {
                  setSuccess(false);
                  setEmail('');
                  setBookingNumber('');
                }}
                className="block w-full px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors"
              >
                {t('try_again') || 'Try Another Booking'}
              </button>
              <Link
                href="/"
                className="block w-full px-6 py-3 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors text-center"
              >
                {tCommon('go_home') || 'Go to Homepage'}
              </Link>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
      <div className="max-w-md w-full">
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
          {/* Header */}
          <div className="text-center mb-8">
            <div className="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg
                className="w-8 h-8 text-primary"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                />
              </svg>
            </div>
            <h1 className="text-2xl font-bold text-gray-900">
              {t('recover_booking') || 'Access Your Booking'}
            </h1>
            <p className="text-gray-600 mt-2">
              {t('recover_booking_description') ||
                'Enter your email and booking number to receive a new secure link to access your booking.'}
            </p>
          </div>

          {/* Form */}
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">
                {t('email_address') || 'Email Address'}
              </label>
              <input
                type="email"
                id="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
                placeholder="you@example.com"
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
              />
            </div>

            <div>
              <label
                htmlFor="bookingNumber"
                className="block text-sm font-medium text-gray-700 mb-1"
              >
                {t('booking_number') || 'Booking Number'}
              </label>
              <input
                type="text"
                id="bookingNumber"
                value={bookingNumber}
                onChange={(e) => setBookingNumber(e.target.value.toUpperCase())}
                required
                placeholder="GA-202512-XXXXX"
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent font-mono"
              />
              <p className="text-xs text-gray-500 mt-1">
                {t('booking_number_hint') || 'Find this in your confirmation email'}
              </p>
            </div>

            {error && (
              <div className="p-4 bg-red-50 border border-red-200 rounded-lg">
                <p className="text-sm text-red-800">{error}</p>
              </div>
            )}

            <button
              type="submit"
              disabled={isSubmitting || !email || !bookingNumber}
              className="w-full px-6 py-3 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isSubmitting
                ? t('sending') || 'Sending...'
                : t('send_access_link') || 'Send Access Link'}
            </button>
          </form>

          {/* Help Text */}
          <div className="mt-8 pt-6 border-t border-gray-200">
            <p className="text-sm text-gray-600 text-center">
              {t('need_help') || "Can't find your booking?"}{' '}
              <a href="mailto:support@goadventure.com" className="text-primary hover:underline">
                {t('contact_support') || 'Contact Support'}
              </a>
            </p>
          </div>
        </div>

        {/* Back Link */}
        <div className="mt-6 text-center">
          <Link href="/" className="text-sm text-gray-600 hover:text-gray-900">
            &larr; {tCommon('back_to_home') || 'Back to Homepage'}
          </Link>
        </div>
      </div>
    </div>
  );
}
