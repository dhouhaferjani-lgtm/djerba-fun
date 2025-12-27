'use client';

import { useEffect, useState, useMemo } from 'react';
import { useParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { Link, useRouter } from '@/i18n/navigation';
import { useCurrentUser, useBooking, useCancelBooking } from '@/lib/api/hooks';
import { getGuestSessionId } from '@/lib/utils/session';
import type { BookingStatus } from '@go-adventure/schemas';

export default function BookingDetailPage() {
  const t = useTranslations('dashboard');
  const params = useParams();
  const router = useRouter();
  const bookingId = params.id as string;

  // Check if user is authenticated or has a guest session
  const isGuest = useMemo(() => {
    if (typeof window === 'undefined') return false;
    return !localStorage.getItem('auth_token');
  }, []);

  const hasGuestSession = useMemo(() => {
    if (typeof window === 'undefined') return false;
    return !!getGuestSessionId();
  }, []);

  const { data: user, isLoading: isLoadingUser } = useCurrentUser();
  const { data: booking, isLoading: isLoadingBooking } = useBooking(bookingId, isGuest);
  const cancelBookingMutation = useCancelBooking();

  const [showCancelDialog, setShowCancelDialog] = useState(false);
  const [cancelReason, setCancelReason] = useState('');

  useEffect(() => {
    // Only redirect to login if not authenticated AND no guest session
    if (!isLoadingUser && !user && !hasGuestSession) {
      router.push('/auth/login');
    }
  }, [user, isLoadingUser, hasGuestSession, router]);

  const handleCancelBooking = async () => {
    try {
      await cancelBookingMutation.mutateAsync({
        id: bookingId,
        reason: cancelReason,
      });
      setShowCancelDialog(false);
      // Refresh booking data
    } catch (error) {
      console.error('Failed to cancel booking:', error);
    }
  };

  const formatPrice = (amount: number, currency: string) => {
    // Prices are stored as whole amounts (e.g., 65 = €65), not cents
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
      payment_pending: 'bg-warning-light text-warning-dark',
      confirmed: 'bg-success-light text-success-dark',
      completed: 'bg-success-light text-success-dark',
      cancelled: 'bg-error-light text-error-dark',
      refunded: 'bg-gray-100 text-gray-800',
      no_show: 'bg-warning-light text-warning-dark',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
  };

  const canCancelBooking = () => {
    if (!booking) return false;
    if (booking.status === 'cancelled' || booking.status === 'completed') return false;
    // Check if booking is in the future
    if (!booking.startsAt) return false;
    return new Date(booking.startsAt) > new Date();
  };

  if (isLoadingUser || isLoadingBooking) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>
    );
  }

  if (!booking) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <h2 className="text-2xl font-bold text-gray-900 mb-2">{t('booking_not_found')}</h2>
          <Link href="/dashboard/bookings" className="text-primary hover:text-primary/80">
            {t('back_to_bookings')}
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 py-12">
      <div className="container mx-auto px-4">
        <div className="max-w-4xl mx-auto">
          {/* Header */}
          <div className="mb-8">
            <Link
              href="/dashboard/bookings"
              className="text-primary hover:text-primary/80 font-medium mb-4 inline-block"
            >
              ← {t('back_to_bookings')}
            </Link>
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

            {/* Traveler Information */}
            {booking.travelers && booking.travelers.length > 0 && (
              <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 className="text-xl font-bold text-gray-900 mb-4">{t('traveler_info')}</h2>
                <div className="space-y-3">
                  {booking.travelers.map((traveler, index) => (
                    <div key={index} className="pb-3 border-b last:border-b-0">
                      <p className="font-medium text-gray-900">
                        {traveler.firstName} {traveler.lastName}
                      </p>
                      <p className="text-sm text-gray-600">{traveler.email}</p>
                      {traveler.phone && <p className="text-sm text-gray-600">{traveler.phone}</p>}
                      {traveler.specialRequests && (
                        <div className="mt-2">
                          <p className="text-sm font-medium text-gray-700">
                            {t('special_requests')}:
                          </p>
                          <p className="text-sm text-gray-600">{traveler.specialRequests}</p>
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              </div>
            )}

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
                {booking.extras && booking.extras.length > 0 && (
                  <>
                    <div className="border-t pt-3">
                      <p className="text-sm font-medium text-gray-700 mb-2">{t('extras')}</p>
                      {booking.extras.map((extra, index) => (
                        <div key={index} className="flex justify-between text-sm mb-1">
                          <span className="text-gray-600">
                            {extra.name} × {extra.quantity}
                          </span>
                          <span className="text-gray-900">
                            {formatPrice(
                              extra.totalPrice ?? (extra.unitPrice ?? 0) * extra.quantity,
                              booking.currency
                            )}
                          </span>
                        </div>
                      ))}
                    </div>
                  </>
                )}
                {booking.discountAmount > 0 && (
                  <div className="flex justify-between text-sm text-success">
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

            {/* Payment Information */}
            {booking.latestPaymentIntent && (
              <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 className="text-xl font-bold text-gray-900 mb-4">{t('payment_info')}</h2>
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between">
                    <span className="text-gray-600">{t('payment_status')}:</span>
                    <span className="font-medium text-gray-900">
                      {booking.latestPaymentIntent.status}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">{t('payment_method')}:</span>
                    <span className="font-medium text-gray-900">
                      {booking.latestPaymentIntent.gateway}
                    </span>
                  </div>
                </div>
              </div>
            )}

            {/* Participants & Vouchers - show for confirmed bookings */}
            {booking.status === 'confirmed' && (booking.guests ?? booking.quantity) > 0 && (
              <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 className="text-xl font-bold text-gray-900 mb-4">
                  {t('participants_vouchers') || 'Participants & Vouchers'}
                </h2>
                <p className="text-sm text-gray-600 mb-4">
                  {t('participants_vouchers_desc') ||
                    'Manage participant names and download vouchers with QR codes for check-in.'}
                </p>
                <div className="flex flex-col sm:flex-row gap-3">
                  <Link
                    href={`/dashboard/bookings/${bookingId}/participants`}
                    className="flex-1 px-4 py-3 border border-primary text-primary rounded-lg font-medium text-center hover:bg-primary/5 transition-colors"
                  >
                    {t('manage_participants') || 'Manage Participants'}
                  </Link>
                  <Link
                    href={`/dashboard/bookings/${bookingId}/vouchers`}
                    className="flex-1 px-4 py-3 bg-primary text-white rounded-lg font-medium text-center hover:bg-primary/90 transition-colors"
                  >
                    {t('view_vouchers') || 'View Vouchers'}
                  </Link>
                </div>
              </div>
            )}

            {/* Actions */}
            <div className="flex gap-4">
              {canCancelBooking() && (
                <button
                  onClick={() => setShowCancelDialog(true)}
                  className="px-6 py-3 border border-error text-error rounded-lg font-medium hover:bg-error-light transition-colors"
                >
                  {t('cancel_booking')}
                </button>
              )}
              <button className="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                {t('download_receipt')}
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Cancel Dialog */}
      {showCancelDialog && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 className="text-xl font-bold text-gray-900 mb-4">{t('cancel_booking')}</h3>
            <p className="text-gray-600 mb-4">{t('cancel_confirm')}</p>
            <div className="mb-4">
              <label
                htmlFor="cancelReason"
                className="block text-sm font-medium text-gray-700 mb-2"
              >
                {t('cancel_reason')} ({t('optional')})
              </label>
              <textarea
                id="cancelReason"
                value={cancelReason}
                onChange={(e) => setCancelReason(e.target.value)}
                rows={3}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                placeholder={t('cancel_reason_placeholder')}
              />
            </div>
            <div className="flex gap-4">
              <button
                onClick={() => setShowCancelDialog(false)}
                className="flex-1 px-6 py-3 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 transition-colors"
              >
                {t('keep_booking')}
              </button>
              <button
                onClick={handleCancelBooking}
                disabled={cancelBookingMutation.isPending}
                className="flex-1 px-6 py-3 bg-error text-white rounded-lg font-medium hover:bg-error-dark transition-colors disabled:opacity-50"
              >
                {cancelBookingMutation.isPending ? t('cancelling') : t('confirm_cancel')}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
