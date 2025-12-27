'use client';

import { useEffect, useState } from 'react';
import { useParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { Link } from '@/i18n/navigation';
import { magicLinksApi, type MagicLinkBookingResponse } from '@/lib/api/client';
import type { BookingStatus } from '@go-adventure/schemas';

export default function MagicLinkBookingPage() {
  const params = useParams();
  const token = params.token as string;
  const t = useTranslations('booking');
  const tCommon = useTranslations('common');

  const [booking, setBooking] = useState<MagicLinkBookingResponse['data'] | null>(null);
  const [magicLinks, setMagicLinks] = useState<MagicLinkBookingResponse['magic_links'] | null>(
    null
  );
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<{
    message: string;
    expired?: boolean;
    bookingNumber?: string;
  } | null>(null);

  useEffect(() => {
    const fetchBooking = async () => {
      try {
        const response = await magicLinksApi.getBooking(token);
        setBooking(response.data);
        setMagicLinks(response.magic_links);
      } catch (err: unknown) {
        const apiError = err as { status?: number; message?: string };
        if (apiError.status === 410) {
          setError({
            message: 'This link has expired.',
            expired: true,
          });
        } else if (apiError.status === 404) {
          setError({ message: 'Invalid or unknown booking link.' });
        } else {
          setError({ message: apiError.message || 'Failed to load booking.' });
        }
      } finally {
        setIsLoading(false);
      }
    };

    fetchBooking();
  }, [token]);

  const formatPrice = (amount: number, currency: string) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency,
    }).format(amount);
  };

  const formatDateTime = (dateString: string | null | undefined) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
      dateStyle: 'full',
      timeStyle: 'short',
    }).format(date);
  };

  const getStatusBadgeColor = (status: BookingStatus) => {
    const colors: Record<BookingStatus, string> = {
      draft: 'bg-gray-100 text-gray-800',
      payment_pending: 'bg-yellow-100 text-yellow-800',
      confirmed: 'bg-green-100 text-green-800',
      completed: 'bg-green-100 text-green-800',
      cancelled: 'bg-red-100 text-red-800',
      refunded: 'bg-gray-100 text-gray-800',
      no_show: 'bg-orange-100 text-orange-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
  };

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="max-w-md w-full mx-4">
          <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
            <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg
                className="w-8 h-8 text-red-600"
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
            <h2 className="text-xl font-bold text-gray-900 mb-2">{error.message}</h2>
            {error.expired && (
              <p className="text-gray-600 mb-6">
                Your booking link has expired for security reasons. You can request a new link
                below.
              </p>
            )}
            <div className="space-y-3">
              <Link
                href="/booking/recover"
                className="block w-full px-6 py-3 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors text-center"
              >
                Request New Link
              </Link>
              <Link
                href="/"
                className="block w-full px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors text-center"
              >
                Go to Homepage
              </Link>
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (!booking) {
    return null;
  }

  return (
    <div className="min-h-screen bg-gray-50 py-12">
      <div className="container mx-auto px-4">
        <div className="max-w-4xl mx-auto">
          {/* Header */}
          <div className="mb-8">
            <div className="flex items-start justify-between">
              <div>
                <h1 className="text-3xl font-bold text-gray-900 mb-2">
                  {t('booking')} #{booking.code}
                </h1>
                <p className="text-gray-600">{formatDateTime(booking.createdAt)}</p>
              </div>
              <span
                className={`px-4 py-2 text-sm font-medium rounded-full ${getStatusBadgeColor(
                  booking.status as BookingStatus
                )}`}
              >
                {booking.status}
              </span>
            </div>
          </div>

          {/* Main Content */}
          <div className="space-y-6">
            {/* Activity Details */}
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h2 className="text-xl font-bold text-gray-900 mb-4">
                {t('activity_details') || 'Activity Details'}
              </h2>
              <div className="space-y-2 text-sm text-gray-600">
                <p>
                  <strong>{t('date_time') || 'Date & Time'}:</strong>{' '}
                  {formatDateTime(booking.startsAt)}
                </p>
                <p>
                  <strong>{t('guests') || 'Guests'}:</strong> {booking.guests ?? booking.quantity}
                </p>
              </div>
            </div>

            {/* Price Breakdown */}
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h2 className="text-xl font-bold text-gray-900 mb-4">
                {t('price_breakdown') || 'Price Breakdown'}
              </h2>
              <div className="space-y-3">
                <div className="flex justify-between text-sm">
                  <span className="text-gray-600">{t('subtotal') || 'Subtotal'}</span>
                  <span className="font-medium text-gray-900">
                    {formatPrice(booking.totalAmount + booking.discountAmount, booking.currency)}
                  </span>
                </div>
                {booking.discountAmount > 0 && (
                  <div className="flex justify-between text-sm text-green-600">
                    <span>{t('discount') || 'Discount'}</span>
                    <span>-{formatPrice(booking.discountAmount, booking.currency)}</span>
                  </div>
                )}
                <div className="border-t pt-3">
                  <div className="flex justify-between">
                    <span className="font-bold text-gray-900">{t('total') || 'Total'}</span>
                    <span className="text-xl font-bold text-primary">
                      {formatPrice(booking.totalAmount, booking.currency)}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            {/* Quick Actions */}
            {booking.status === 'confirmed' && (
              <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 className="text-xl font-bold text-gray-900 mb-4">
                  {t('manage_booking') || 'Manage Your Booking'}
                </h2>
                <p className="text-sm text-gray-600 mb-4">
                  Enter participant names and download your vouchers for check-in.
                </p>
                <div className="flex flex-col sm:flex-row gap-3">
                  <Link
                    href={`/booking/${token}/participants`}
                    className="flex-1 px-4 py-3 border border-primary text-primary rounded-lg font-medium text-center hover:bg-primary/5 transition-colors"
                  >
                    {t('manage_participants') || 'Enter Participant Names'}
                  </Link>
                  <Link
                    href={`/booking/${token}/vouchers`}
                    className="flex-1 px-4 py-3 bg-primary text-white rounded-lg font-medium text-center hover:bg-primary/90 transition-colors"
                  >
                    {t('view_vouchers') || 'Download Vouchers'}
                  </Link>
                </div>
              </div>
            )}

            {/* Security Notice */}
            <div className="bg-amber-50 border border-amber-200 rounded-lg p-4">
              <div className="flex items-start gap-3">
                <svg
                  className="w-5 h-5 text-amber-600 mt-0.5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
                <div className="text-sm text-amber-800">
                  <p className="font-medium mb-1">
                    {t('secure_link_notice') || 'This is a secure booking link'}
                  </p>
                  <p>
                    {t('secure_link_description') ||
                      'Bookmark this page or save the email with your booking confirmation to access your booking anytime.'}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
