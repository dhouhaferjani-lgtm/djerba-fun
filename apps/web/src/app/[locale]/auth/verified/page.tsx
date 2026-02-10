'use client';

import { useEffect, useState, useCallback } from 'react';
import { useSearchParams, useRouter, useParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { Link } from '@/i18n/navigation';
import { Button } from '@go-adventure/ui';
import { Check, Loader2, Package, AlertCircle } from 'lucide-react';
import { useClaimableBookings, useLinkBookings } from '@/lib/api/hooks';
import { authApi } from '@/lib/api/client';
import type { Booking } from '@go-adventure/schemas';

/**
 * Email verification success page
 * 1. Verifies the token from the email link
 * 2. Auto-logs in user (stores API token)
 * 3. Checks for claimable bookings
 * 4. Allows linking of guest bookings to the new account
 */
export default function EmailVerifiedPage() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const params = useParams();
  const locale = params.locale as string;
  const t = useTranslations('auth');
  const tBooking = useTranslations('booking');

  const [selectedBookingIds, setSelectedBookingIds] = useState<string[]>([]);
  const [verificationState, setVerificationState] = useState<
    'verifying' | 'verified' | 'expired' | 'error'
  >('verifying');
  const [resendEmail, setResendEmail] = useState('');
  const [resendSuccess, setResendSuccess] = useState(false);

  // Get token from URL (sent in verification email)
  const token = searchParams.get('token');

  // Only query claimable bookings AFTER verification succeeds
  const { data: claimableData, isLoading: bookingsLoading } = useClaimableBookings(
    verificationState === 'verified'
  );
  const linkBookingsMutation = useLinkBookings();

  const claimableBookings = claimableData || [];
  const hasClaimableBookings = claimableBookings.length > 0;

  // Verify the token on mount
  useEffect(() => {
    if (!token) {
      setVerificationState('error');
      return;
    }

    const verify = async () => {
      try {
        await authApi.verifyEmail(token);
        setVerificationState('verified');
      } catch {
        setVerificationState('expired');
      }
    };

    verify();
  }, [token]);

  // Auto-select all bookings by default
  useEffect(() => {
    if (claimableBookings.length > 0) {
      setSelectedBookingIds(claimableBookings.map((b) => b.id));
    }
  }, [claimableBookings]);

  const handleResend = async () => {
    if (!resendEmail) return;
    try {
      await authApi.resendVerification(resendEmail);
      setResendSuccess(true);
    } catch {
      // Silent fail
    }
  };

  const handleLinkBookings = async () => {
    try {
      await linkBookingsMutation.mutateAsync(selectedBookingIds);
      setTimeout(() => {
        router.push('/dashboard');
      }, 1500);
    } catch (error) {
      console.error('Failed to link bookings:', error);
    }
  };

  const handleSkip = useCallback(() => {
    router.push('/dashboard');
  }, [router]);

  const toggleBookingSelection = (bookingId: string) => {
    setSelectedBookingIds((prev) =>
      prev.includes(bookingId) ? prev.filter((id) => id !== bookingId) : [...prev, bookingId]
    );
  };

  const formatDate = (dateString: string | undefined) => {
    if (!dateString) return 'N/A';
    try {
      return new Intl.DateTimeFormat('en-US', {
        dateStyle: 'medium',
      }).format(new Date(dateString));
    } catch {
      return 'N/A';
    }
  };

  const formatCurrency = (amount: number, currency: string) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency,
    }).format(amount);
  };

  // --- Verifying state ---
  if (verificationState === 'verifying') {
    return (
      <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4">
        <div className="max-w-md w-full bg-white rounded-lg shadow-md p-8">
          <div className="text-center">
            <div className="flex justify-center mb-6">
              <div className="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center">
                <Loader2 className="w-10 h-10 text-primary animate-spin" />
              </div>
            </div>
            <h1 className="text-2xl font-bold text-gray-900 mb-2">
              {t('verifying_account') || 'Verifying your email...'}
            </h1>
            <p className="text-gray-600">{t('please_wait') || 'Please wait a moment'}</p>
          </div>
        </div>
      </div>
    );
  }

  // --- Expired / Error state ---
  if (verificationState === 'expired' || verificationState === 'error') {
    return (
      <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4">
        <div className="max-w-md w-full bg-white rounded-lg shadow-md p-8">
          <div className="text-center">
            <div className="flex justify-center mb-6">
              <div className="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center">
                <AlertCircle className="w-10 h-10 text-red-500" />
              </div>
            </div>
            <h1 className="text-2xl font-bold text-gray-900 mb-2">
              {t('verification_expired') || 'Verification link expired'}
            </h1>
            <p className="text-gray-600 mb-6">
              {t('verification_expired_message') ||
                'This link has expired or is invalid. Enter your email to receive a new one.'}
            </p>

            {resendSuccess ? (
              <div className="p-3 bg-success-light border border-success/20 rounded-lg text-success-dark text-sm mb-4">
                {t('verify_email_resent') || 'A new verification email has been sent!'}
              </div>
            ) : (
              <div className="space-y-3">
                <input
                  type="email"
                  value={resendEmail}
                  onChange={(e) => setResendEmail(e.target.value)}
                  placeholder={t('email') || 'Email address'}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none"
                />
                <Button
                  variant="primary"
                  className="w-full"
                  onClick={handleResend}
                  disabled={!resendEmail}
                >
                  {t('verify_email_resend') || 'Resend verification email'}
                </Button>
              </div>
            )}

            <div className="mt-4">
              <Link
                href={`/auth/login`}
                className="text-sm text-gray-600 hover:text-primary transition-colors"
              >
                {t('back_to_login') || 'Back to login'}
              </Link>
            </div>
          </div>
        </div>
      </div>
    );
  }

  // --- Verified: checking bookings ---
  if (bookingsLoading) {
    return (
      <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4">
        <div className="max-w-md w-full bg-white rounded-lg shadow-md p-8">
          <div className="text-center">
            <div className="flex justify-center mb-6">
              <div className="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center">
                <Loader2 className="w-10 h-10 text-primary animate-spin" />
              </div>
            </div>
            <h1 className="text-2xl font-bold text-gray-900 mb-2">
              {t('verifying_account') || 'Setting up your account...'}
            </h1>
            <p className="text-gray-600">
              {t('checking_bookings') || 'Checking for your bookings'}
            </p>
          </div>
        </div>
      </div>
    );
  }

  // --- Bookings linked success ---
  if (linkBookingsMutation.isSuccess) {
    return (
      <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4">
        <div className="max-w-md w-full bg-white rounded-lg shadow-md p-8">
          <div className="text-center">
            <div className="flex justify-center mb-6">
              <div className="w-16 h-16 bg-success-light rounded-full flex items-center justify-center">
                <Check className="w-10 h-10 text-success" />
              </div>
            </div>
            <h1 className="text-2xl font-bold text-gray-900 mb-2">
              {t('bookings_linked') || 'Bookings linked successfully!'}
            </h1>
            <p className="text-gray-600 mb-6">
              {t('redirecting_dashboard') || 'Redirecting to your dashboard...'}
            </p>
            <div className="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
              <div
                className="bg-primary h-full transition-all duration-[1500ms] ease-linear"
                style={{ width: '100%' }}
              />
            </div>
          </div>
        </div>
      </div>
    );
  }

  // --- Verified: main view ---
  return (
    <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4">
      <div className="max-w-2xl w-full bg-white rounded-lg shadow-md p-8">
        {/* Success Header */}
        <div className="text-center mb-8">
          <div className="flex justify-center mb-6">
            <div className="w-16 h-16 bg-success-light rounded-full flex items-center justify-center">
              <Check className="w-10 h-10 text-success" />
            </div>
          </div>
          <h1 className="text-2xl font-bold text-gray-900 mb-2">
            {t('account_verified') || 'Account Verified!'}
          </h1>
          <p className="text-gray-600">
            {t('welcome_message') || 'Welcome to Go Adventure! Your account is now active.'}
          </p>
        </div>

        {/* Bookings Found */}
        {hasClaimableBookings ? (
          <div>
            <div className="bg-primary/5 border border-primary/20 rounded-lg p-6 mb-6">
              <div className="flex items-start gap-3 mb-4">
                <Package className="w-6 h-6 text-primary flex-shrink-0 mt-1" />
                <div>
                  <h2 className="font-bold text-gray-900 text-lg mb-1">
                    {t('bookings_found_title') ||
                      `We found ${claimableBookings.length} booking${claimableBookings.length > 1 ? 's' : ''}!`}
                  </h2>
                  <p className="text-sm text-gray-700">
                    {t('bookings_found_message') ||
                      'Link these bookings to your account to manage them easily.'}
                  </p>
                </div>
              </div>
            </div>

            {/* Bookings List */}
            <div className="space-y-3 mb-6">
              {claimableBookings.map((booking: Booking) => {
                const isSelected = selectedBookingIds.includes(booking.id);

                return (
                  <div
                    key={booking.id}
                    onClick={() => toggleBookingSelection(booking.id)}
                    className={`border rounded-lg p-4 cursor-pointer transition-all ${
                      isSelected
                        ? 'border-primary bg-primary/5'
                        : 'border-gray-200 hover:border-gray-300'
                    }`}
                  >
                    <div className="flex items-start gap-3">
                      {/* Checkbox */}
                      <div className="flex-shrink-0 mt-1">
                        <div
                          className={`w-5 h-5 rounded border-2 flex items-center justify-center ${
                            isSelected ? 'bg-primary border-primary' : 'border-gray-300 bg-white'
                          }`}
                        >
                          {isSelected && <Check className="w-3 h-3 text-white" strokeWidth={3} />}
                        </div>
                      </div>

                      {/* Booking Details */}
                      <div className="flex-1 min-w-0">
                        <div className="flex items-start justify-between gap-4">
                          <div>
                            <p className="font-semibold text-gray-900">
                              {booking.bookingNumber || booking.id}
                            </p>
                            <p className="text-sm text-gray-600 mt-1">
                              {formatDate(booking.createdAt)} •{' '}
                              {formatCurrency(booking.totalAmount, booking.currency)}
                            </p>
                          </div>
                          <span
                            className={`text-xs px-2 py-1 rounded-full ${
                              booking.status === 'confirmed'
                                ? 'bg-success-light text-success-dark'
                                : 'bg-gray-100 text-gray-700'
                            }`}
                          >
                            {booking.status}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>

            {/* Actions */}
            <div className="flex flex-col sm:flex-row gap-3">
              <Button
                onClick={handleLinkBookings}
                variant="primary"
                size="lg"
                className="flex-1"
                isLoading={linkBookingsMutation.isPending}
                disabled={selectedBookingIds.length === 0}
              >
                {t('link_bookings') ||
                  `Link ${selectedBookingIds.length} Booking${selectedBookingIds.length !== 1 ? 's' : ''}`}
              </Button>
              <Button
                onClick={handleSkip}
                variant="outline"
                size="lg"
                className="flex-1"
                disabled={linkBookingsMutation.isPending}
              >
                {t('maybe_later') || 'Maybe Later'}
              </Button>
            </div>

            {/* Error Display */}
            {linkBookingsMutation.isError && (
              <div className="mt-4 p-4 bg-error-light border border-error/20 rounded-lg">
                <p className="text-sm text-error-dark">
                  {linkBookingsMutation.error?.message ||
                    t('link_error') ||
                    'Failed to link bookings. Please try again.'}
                </p>
              </div>
            )}
          </div>
        ) : (
          /* No Bookings Found */
          <div className="text-center">
            <div className="bg-gray-50 rounded-lg p-8 mb-6">
              <p className="text-gray-600">
                {t('no_bookings_found') ||
                  "You're all set! No previous bookings were found to link."}
              </p>
            </div>
            <Link
              href="/dashboard"
              className="inline-flex items-center px-6 py-3 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors"
            >
              {t('go_to_dashboard') || 'Go to Dashboard'}
            </Link>
          </div>
        )}
      </div>
    </div>
  );
}
