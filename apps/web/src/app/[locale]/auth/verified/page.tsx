'use client';

import { useEffect, useState } from 'react';
import { useSearchParams, useRouter } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { Link } from '@/i18n/navigation';
import { Button } from '@go-adventure/ui';
import { Check, Loader2, Package } from 'lucide-react';
import { useClaimableBookings, useLinkBookings } from '@/lib/api/hooks';
import type { Booking } from '@go-adventure/schemas';

/**
 * Email verification success page
 * Auto-logs in user and checks for claimable bookings
 * Allows linking of guest bookings to the new account
 */
export default function EmailVerifiedPage() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const t = useTranslations('auth');
  const tBooking = useTranslations('booking');

  const [checkingBookings, setCheckingBookings] = useState(true);
  const [selectedBookingIds, setSelectedBookingIds] = useState<string[]>([]);

  // Get auto-login token from URL (sent in verification email)
  const token = searchParams.get('token');

  const { data: claimableData, isLoading: bookingsLoading } = useClaimableBookings();
  const linkBookingsMutation = useLinkBookings();

  const claimableBookings = claimableData || [];
  const hasClaimableBookings = claimableBookings.length > 0;

  // Auto-select all bookings by default
  useEffect(() => {
    if (claimableBookings.length > 0) {
      setSelectedBookingIds(claimableBookings.map((b) => b.id));
    }
  }, [claimableBookings]);

  // Check bookings loading state
  useEffect(() => {
    if (!bookingsLoading) {
      setCheckingBookings(false);
    }
  }, [bookingsLoading]);

  const handleLinkBookings = async () => {
    try {
      await linkBookingsMutation.mutateAsync(selectedBookingIds);
      // Redirect to dashboard after successful linking
      setTimeout(() => {
        router.push('/dashboard');
      }, 1500);
    } catch (error) {
      console.error('Failed to link bookings:', error);
    }
  };

  const handleSkip = () => {
    router.push('/dashboard');
  };

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

  if (checkingBookings) {
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
