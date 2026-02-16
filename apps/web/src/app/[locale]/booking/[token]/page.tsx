'use client';

import { useEffect, useState } from 'react';
import { useParams } from 'next/navigation';
import { useTranslations, useLocale } from 'next-intl';
import { Link } from '@/i18n/navigation';
import { magicLinksApi, type MagicLinkBookingResponse } from '@/lib/api/client';
import type { BookingStatus } from '@go-adventure/schemas';

export default function MagicLinkBookingPage() {
  const params = useParams();
  const token = params.token as string;
  const t = useTranslations('booking');
  const locale = useLocale();

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
            message: t('link_expired'),
            expired: true,
          });
        } else if (apiError.status === 404) {
          setError({ message: t('invalid_link') });
        } else {
          setError({ message: t('load_failed') });
        }
      } finally {
        setIsLoading(false);
      }
    };

    fetchBooking();
  }, [token, t]);

  const formatPrice = (amount: number, currency: string) => {
    return new Intl.NumberFormat(locale, {
      style: 'currency',
      currency: currency,
    }).format(amount);
  };

  const formatDateTime = (dateString: string | null | undefined) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat(locale, {
      dateStyle: 'full',
      timeStyle: 'short',
    }).format(date);
  };

  const getStatusBadgeColor = (status: BookingStatus) => {
    const colors: Record<BookingStatus, string> = {
      draft: 'bg-gray-100 text-gray-800',
      payment_pending: 'bg-warning-light text-warning-dark',
      confirmed: 'bg-success-light text-success-dark',
      completed: 'bg-success-light text-success-dark',
      cancelled: 'bg-error-light text-error-dark',
      refunded: 'bg-gray-100 text-gray-800',
      no_show: 'bg-warning-light text-warning-dark',
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
            <div className="w-16 h-16 bg-error-light rounded-full flex items-center justify-center mx-auto mb-4">
              <svg
                className="w-8 h-8 text-error-dark"
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
            {error.expired && <p className="text-gray-600 mb-6">{t('link_expired_description')}</p>}
            <div className="space-y-3">
              <Link
                href="/booking/recover"
                className="block w-full px-6 py-3 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors text-center"
              >
                {t('request_new_link')}
              </Link>
              <Link
                href="/"
                className="block w-full px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors text-center"
              >
                {t('go_to_homepage')}
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
                  {t('magic_link_booking')} #{booking.code}
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
              <h2 className="text-xl font-bold text-gray-900 mb-4">{t('activity_details')}</h2>
              <div className="space-y-2 text-sm text-gray-600">
                <p>
                  <strong>{t('date_time')}:</strong> {formatDateTime(booking.startsAt)}
                </p>
                <p>
                  <strong>{t('guests')}:</strong> {booking.guests ?? booking.quantity}
                </p>
              </div>
            </div>

            {/* Price Breakdown */}
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h2 className="text-xl font-bold text-gray-900 mb-4">{t('price_breakdown')}</h2>
              <div className="space-y-3">
                <div className="flex justify-between text-sm">
                  <span className="text-gray-600">{t('subtotal')}</span>
                  <span className="font-medium text-gray-900">
                    {formatPrice(booking.totalAmount + booking.discountAmount, booking.currency)}
                  </span>
                </div>
                {booking.discountAmount > 0 && (
                  <div className="flex justify-between text-sm text-success-dark">
                    <span>{t('discount')}</span>
                    <span>-{formatPrice(booking.discountAmount, booking.currency)}</span>
                  </div>
                )}
                <div className="border-t pt-3">
                  <div className="flex justify-between">
                    <span className="font-bold text-gray-900">{t('total')}</span>
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
                <h2 className="text-xl font-bold text-gray-900 mb-4">{t('manage_booking')}</h2>
                <p className="text-sm text-gray-600 mb-4">{t('manage_booking_description')}</p>
                <div className="flex flex-col sm:flex-row gap-3">
                  <Link
                    href={`/booking/${token}/participants`}
                    className="flex-1 px-4 py-3 border border-primary text-primary rounded-lg font-medium text-center hover:bg-primary/5 transition-colors"
                  >
                    {t('manage_participants')}
                  </Link>
                  <Link
                    href={`/booking/${token}/vouchers`}
                    className="flex-1 px-4 py-3 bg-primary text-white rounded-lg font-medium text-center hover:bg-primary/90 transition-colors"
                  >
                    {t('view_vouchers')}
                  </Link>
                </div>
              </div>
            )}

            {/* Security Notice */}
            <div className="bg-warning-light border border-warning rounded-lg p-4">
              <div className="flex items-start gap-3">
                <svg
                  className="w-5 h-5 text-warning-dark mt-0.5"
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
                <div className="text-sm text-warning-dark">
                  <p className="font-medium mb-1">{t('secure_link_notice')}</p>
                  <p>{t('secure_link_description')}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
