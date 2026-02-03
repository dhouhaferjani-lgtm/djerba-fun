'use client';

import { useMemo } from 'react';
import { useSearchParams } from 'next/navigation';
import { useTranslations, useLocale } from 'next-intl';
import { useQueries } from '@tanstack/react-query';
import { Link } from '@/i18n/navigation';
import { vouchersApi } from '@/lib/api/client';
import { getGuestSessionId } from '@/lib/utils/session';
import { QRCodeSVG } from 'qrcode.react';
import { Button } from '@go-adventure/ui';
import { Printer, Download, ChevronLeft, CheckCircle, AlertTriangle, Loader2 } from 'lucide-react';

/**
 * Cart Vouchers Page
 *
 * Displays vouchers for ALL bookings from a cart checkout on one page.
 * URL: /checkout/vouchers?bookings=id1,id2,id3
 */
export default function CartVouchersPage() {
  const searchParams = useSearchParams();
  const locale = useLocale();
  const t = useTranslations('vouchers');
  const tCommon = useTranslations('common');

  // Get booking IDs from URL params
  const bookingIdsParam = searchParams.get('bookings');
  const bookingIds = bookingIdsParam ? bookingIdsParam.split(',').filter(Boolean) : [];

  // Check if user is authenticated - if not, use guest access
  const isGuest = useMemo(() => {
    if (typeof window === 'undefined') return false;
    return !localStorage.getItem('auth_token');
  }, []);

  // Fetch vouchers for ALL bookings using useQueries
  const voucherQueries = useQueries({
    queries: bookingIds.map((id) => ({
      queryKey: ['vouchers', id, isGuest ? 'guest' : 'auth'],
      queryFn: async () => {
        if (isGuest) {
          const sessionId = getGuestSessionId();
          if (!sessionId) throw new Error('No session ID');
          return vouchersApi.listGuest(id, sessionId);
        }
        return vouchersApi.list(id);
      },
    })),
  });

  const isLoading = voucherQueries.some((q) => q.isLoading);
  const hasError = voucherQueries.some((q) => q.isError);

  // Combine all voucher data
  const allVouchersData = voucherQueries
    .map((q, index) => ({
      bookingId: bookingIds[index],
      data: q.data,
      isLoading: q.isLoading,
      isError: q.isError,
    }))
    .filter((v) => v.data);

  // Count totals
  const totalVouchers = allVouchersData.reduce((sum, v) => sum + (v.data?.data?.length || 0), 0);
  const readyBookings = allVouchersData.filter((v) => v.data?.canGenerate).length;
  const totalBookings = bookingIds.length;

  const handlePrintAll = () => {
    window.print();
  };

  // Loading state
  if (isLoading) {
    return (
      <div className="min-h-[60vh] flex items-center justify-center">
        <div className="text-center">
          <Loader2 className="w-12 h-12 text-primary animate-spin mx-auto mb-4" />
          <p className="text-neutral-600">{t('loading') || 'Loading vouchers...'}</p>
        </div>
      </div>
    );
  }

  // No bookings specified
  if (bookingIds.length === 0) {
    return (
      <div className="max-w-4xl mx-auto p-6">
        <div className="text-center py-12">
          <AlertTriangle className="w-16 h-16 text-warning mx-auto mb-4" />
          <h2 className="text-xl font-bold text-neutral-900 mb-2">
            {t('no_bookings') || 'No Bookings Found'}
          </h2>
          <p className="text-neutral-600 mb-6">
            {t('no_bookings_message') || 'No booking IDs were provided.'}
          </p>
          <Link href="/dashboard/bookings">
            <Button>{tCommon('go_to_bookings') || 'Go to My Bookings'}</Button>
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto p-6">
      {/* Header */}
      <div className="mb-8 print:hidden">
        <Link
          href="/dashboard/bookings"
          className="text-sm text-neutral-600 hover:text-neutral-900 mb-4 inline-flex items-center"
        >
          <ChevronLeft className="w-4 h-4 mr-1" />
          {tCommon('back_to_bookings') || 'Back to bookings'}
        </Link>

        <div className="flex items-center justify-between mt-4">
          <div>
            <h1 className="text-2xl font-bold text-neutral-900">
              {t('your_vouchers') || 'Your Vouchers'}
            </h1>
            <p className="text-neutral-600 mt-1">
              {t('cart_subtitle', { bookings: totalBookings, vouchers: totalVouchers }) ||
                `${totalBookings} activities • ${totalVouchers} vouchers`}
            </p>
          </div>

          <Button onClick={handlePrintAll} className="flex items-center gap-2">
            <Printer className="w-5 h-5" />
            {t('print_all') || 'Print All'}
          </Button>
        </div>

        {/* Progress indicator if some bookings not ready */}
        {readyBookings < totalBookings && (
          <div className="mt-4 p-4 bg-warning/10 border border-warning/30 rounded-lg">
            <div className="flex items-center gap-3">
              <AlertTriangle className="w-5 h-5 text-warning" />
              <div>
                <p className="font-medium text-warning-dark">
                  {t('some_not_ready', { ready: readyBookings, total: totalBookings }) ||
                    `${readyBookings} of ${totalBookings} activities ready`}
                </p>
                <p className="text-sm text-warning-dark/80">
                  {t('complete_names_to_view') ||
                    'Complete participant names to view all vouchers.'}
                </p>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Vouchers by Booking */}
      <div className="space-y-8" id="vouchers-container">
        {allVouchersData.map((voucherData, bookingIndex) => {
          const { bookingId, data } = voucherData;

          if (!data) return null;

          const bookingInfo = data.booking;
          const vouchers = data.data || [];
          const canGenerate = data.canGenerate;

          return (
            <div key={bookingId} className="print:break-before-page">
              {/* Activity Header */}
              <div className="bg-primary/5 rounded-xl p-4 mb-4 print:bg-neutral-100">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm text-primary font-medium">
                      {t('activity_number', { number: bookingIndex + 1 }) ||
                        `Activity ${bookingIndex + 1}`}
                    </p>
                    <h2 className="text-lg font-bold text-neutral-900">
                      {bookingInfo?.listingTitle}
                    </h2>
                    <p className="text-sm text-neutral-600">
                      {bookingInfo?.bookingNumber} • {vouchers.length}{' '}
                      {vouchers.length === 1 ? 'participant' : 'participants'}
                    </p>
                  </div>
                  {canGenerate ? (
                    <div className="flex items-center gap-2 text-success">
                      <CheckCircle className="w-5 h-5" />
                      <span className="text-sm font-medium">{t('ready') || 'Ready'}</span>
                    </div>
                  ) : (
                    <Link href={`/dashboard/bookings/${bookingId}/participants`}>
                      <Button variant="outline" size="sm">
                        {t('enter_names') || 'Enter Names'}
                      </Button>
                    </Link>
                  )}
                </div>
              </div>

              {/* Vouchers for this booking */}
              {canGenerate && vouchers.length > 0 ? (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 print:grid-cols-1 print:gap-0">
                  {vouchers.map((voucher, index) => (
                    <div
                      key={voucher.voucherCode}
                      className="bg-white border border-neutral-200 rounded-lg overflow-hidden print:border-2 print:border-dashed print:mb-6 print:break-inside-avoid"
                    >
                      {/* Voucher Header */}
                      <div className="bg-primary text-white p-3 print:bg-neutral-100 print:text-neutral-900">
                        <div className="flex items-center justify-between">
                          <div>
                            <p className="text-xs opacity-80">{t('voucher') || 'Voucher'}</p>
                            <p className="font-bold">{voucher.voucherCode}</p>
                          </div>
                          <div className="text-right">
                            <p className="text-xs opacity-80">#{index + 1}</p>
                          </div>
                        </div>
                      </div>

                      {/* Voucher Body */}
                      <div className="p-4">
                        <div className="flex gap-4">
                          {/* QR Code */}
                          <div className="flex-shrink-0">
                            <div className="bg-white p-1.5 border border-neutral-200 rounded">
                              <QRCodeSVG value={voucher.qrCodeData} size={80} level="M" />
                            </div>
                          </div>

                          {/* Details */}
                          <div className="flex-1 space-y-2 text-sm">
                            <div>
                              <p className="text-neutral-500">
                                {t('participant') || 'Participant'}
                              </p>
                              <p className="font-semibold text-neutral-900">
                                {voucher.participant?.fullName ||
                                  t('name_not_entered') ||
                                  'Name not entered'}
                              </p>
                            </div>
                            <div className="flex gap-3">
                              <div>
                                <p className="text-neutral-500">{t('date') || 'Date'}</p>
                                <p className="font-medium text-neutral-900">
                                  {voucher.event?.date}
                                </p>
                              </div>
                              <div>
                                <p className="text-neutral-500">{t('time') || 'Time'}</p>
                                <p className="font-medium text-neutral-900">
                                  {voucher.event?.time}
                                </p>
                              </div>
                            </div>
                          </div>
                        </div>

                        {/* Check-in Status */}
                        {voucher.participant?.checkedIn && (
                          <div className="mt-3 p-2 bg-success/10 border border-success/30 rounded flex items-center gap-2">
                            <CheckCircle className="w-4 h-4 text-success" />
                            <span className="text-xs font-medium text-success">
                              {t('checked_in') || 'Checked In'}
                            </span>
                          </div>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="bg-warning/10 border border-warning/30 rounded-lg p-6 text-center">
                  <AlertTriangle className="w-10 h-10 text-warning mx-auto mb-3" />
                  <p className="font-medium text-warning-dark mb-2">
                    {t('vouchers_not_ready') || 'Vouchers Not Ready'}
                  </p>
                  <p className="text-sm text-warning-dark/80 mb-4">
                    {t('complete_names_first') || 'Please enter all participant names first.'}
                  </p>
                  <Link href={`/dashboard/bookings/${bookingId}/participants`}>
                    <Button variant="outline" size="sm">
                      {t('enter_names') || 'Enter Participant Names'}
                    </Button>
                  </Link>
                </div>
              )}
            </div>
          );
        })}
      </div>

      {/* Actions footer */}
      <div className="mt-8 pt-6 border-t print:hidden">
        <div className="flex flex-col sm:flex-row gap-4 justify-center">
          <Button onClick={handlePrintAll} className="flex items-center justify-center gap-2">
            <Printer className="w-5 h-5" />
            {t('print_all') || 'Print All Vouchers'}
          </Button>
          <Link href="/dashboard/bookings">
            <Button variant="outline">{tCommon('go_to_bookings') || 'Go to My Bookings'}</Button>
          </Link>
        </div>
      </div>

      {/* Print Styles */}
      <style jsx global>{`
        @media print {
          body * {
            visibility: hidden;
          }
          .print\\:hidden {
            display: none !important;
          }
          #vouchers-container,
          #vouchers-container * {
            visibility: visible;
          }
          #vouchers-container {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
          }
        }
      `}</style>
    </div>
  );
}
