'use client';

import { useState, useMemo } from 'react';
import { useSearchParams } from 'next/navigation';
import { useTranslations, useLocale } from 'next-intl';
import { useQueries } from '@tanstack/react-query';
import { Link } from '@/i18n/navigation';
import { vouchersApi } from '@/lib/api/client';
import { getGuestSessionId } from '@/lib/utils/session';
import { QRCodeSVG } from 'qrcode.react';
import { Button } from '@djerba-fun/ui';
import {
  Printer,
  Download,
  ChevronLeft,
  ChevronDown,
  CheckCircle,
  AlertTriangle,
  Loader2,
  Ticket,
} from 'lucide-react';

/**
 * Cart Vouchers Page - Accordion View
 *
 * Displays vouchers for ALL bookings from a cart checkout.
 * Each activity is a collapsible accordion section.
 * URL: /checkout/vouchers?bookings=id1,id2,id3
 */
export default function CartVouchersPage() {
  const searchParams = useSearchParams();
  const locale = useLocale();
  const t = useTranslations('vouchers');
  const tCommon = useTranslations('common');

  // Track which accordions are expanded (all expanded by default)
  const [expandedBookings, setExpandedBookings] = useState<Record<string, boolean>>({});

  // Get booking IDs from URL params
  const bookingIdsParam = searchParams.get('bookings');
  const bookingIds = bookingIdsParam ? bookingIdsParam.split(',').filter(Boolean) : [];

  // Check if user is authenticated - if not, use guest access
  const isGuest = useMemo(() => {
    if (typeof window === 'undefined') return false;
    return !localStorage.getItem('auth_token');
  }, []);

  // Helper to extract string from translatable object or return string directly
  // Handles cases where API returns {en: "...", fr: "..."} instead of plain string
  const getLocalizedString = (
    value: string | Record<string, string> | null | undefined,
    fallback: string = ''
  ): string => {
    if (!value) return fallback;
    if (typeof value === 'string') return value;
    // It's a translatable object like {en: "...", fr: "..."}
    return value[locale] || value['en'] || value['fr'] || Object.values(value)[0] || fallback;
  };

  // Fetch vouchers for ALL bookings using useQueries
  const voucherQueries = useQueries({
    queries: bookingIds.map((id) => ({
      queryKey: ['vouchers', id, isGuest ? 'guest' : 'auth'],
      queryFn: async () => {
        if (isGuest) {
          const sessionId = getGuestSessionId();
          if (!sessionId) {
            console.error('Guest session ID not found for booking:', id);
            // Return empty state instead of throwing - allows page to render with helpful message
            return { data: [], canGenerate: false, booking: null };
          }
          return vouchersApi.listGuest(id, sessionId);
        }
        return vouchersApi.list(id);
      },
      retry: 1,
    })),
  });

  const isLoading = voucherQueries.some((q) => q.isLoading);
  const hasError = voucherQueries.some((q) => q.isError);
  const allErrors = voucherQueries.every((q) => q.isError);

  // Combine all voucher data
  const allVouchersData = voucherQueries
    .map((q, index) => ({
      bookingId: bookingIds[index],
      data: q.data,
      isLoading: q.isLoading,
      isError: q.isError,
      error: q.error,
    }))
    .filter((v) => v.data || v.isError);

  // Count totals
  const totalVouchers = allVouchersData.reduce((sum, v) => sum + (v.data?.data?.length || 0), 0);
  const readyBookings = allVouchersData.filter((v) => v.data?.canGenerate).length;
  const totalBookings = bookingIds.length;

  // Initialize expanded state on first load
  useMemo(() => {
    if (allVouchersData.length > 0 && Object.keys(expandedBookings).length === 0) {
      const initial: Record<string, boolean> = {};
      // Expand first booking by default
      if (bookingIds[0]) {
        initial[bookingIds[0]] = true;
      }
      setExpandedBookings(initial);
    }
  }, [allVouchersData.length, bookingIds, expandedBookings]);

  const toggleAccordion = (bookingId: string) => {
    setExpandedBookings((prev) => ({
      ...prev,
      [bookingId]: !prev[bookingId],
    }));
  };

  const handlePrintAll = () => {
    window.print();
  };

  const handleDownloadPdf = async (bookingId: string) => {
    // For now, just trigger print for that section
    // In future, could implement individual PDF generation via API
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

  // All queries failed - show error
  if (allErrors) {
    return (
      <div className="max-w-4xl mx-auto p-6">
        <div className="text-center py-12">
          <AlertTriangle className="w-16 h-16 text-error mx-auto mb-4" />
          <h2 className="text-xl font-bold text-neutral-900 mb-2">
            {t('error_loading') || 'Error Loading Vouchers'}
          </h2>
          <p className="text-neutral-600 mb-6">
            {t('error_loading_message') ||
              "We couldn't load your vouchers. Please try again or contact support."}
          </p>
          <div className="flex gap-4 justify-center">
            <Button onClick={() => window.location.reload()} variant="outline">
              {tCommon('try_again') || 'Try Again'}
            </Button>
            <Link href="/dashboard/bookings">
              <Button>{tCommon('go_to_bookings') || 'Go to My Bookings'}</Button>
            </Link>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-3xl mx-auto px-4 py-8">
      {/* Header */}
      <div className="mb-8 print:hidden">
        <Link
          href="/dashboard/bookings"
          className="text-sm text-neutral-600 hover:text-neutral-900 mb-4 inline-flex items-center"
        >
          <ChevronLeft className="w-4 h-4 mr-1" />
          {tCommon('back_to_bookings') || 'Back to bookings'}
        </Link>

        <div className="text-center mt-6">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/10 text-primary mb-4">
            <Ticket className="w-8 h-8" />
          </div>
          <h1 className="text-2xl font-bold text-neutral-900">
            {t('your_vouchers') || 'Your Vouchers'}
          </h1>
          <p className="text-neutral-600 mt-1">
            {t('cart_subtitle', { bookings: totalBookings, vouchers: totalVouchers }) ||
              `${totalBookings} activities • ${totalVouchers} vouchers`}
          </p>
        </div>

        {/* Progress indicator if some bookings not ready */}
        {readyBookings < totalBookings && (
          <div className="mt-6 p-4 bg-warning/10 border border-warning/30 rounded-xl">
            <div className="flex items-center gap-3">
              <AlertTriangle className="w-5 h-5 text-warning flex-shrink-0" />
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

      {/* Activity Accordions */}
      <div className="space-y-4 mb-8" id="vouchers-container">
        {allVouchersData.map((voucherData, bookingIndex) => {
          const { bookingId, data, isError } = voucherData;
          const isExpanded = expandedBookings[bookingId] || false;

          // Show error for this specific booking
          if (isError) {
            return (
              <div
                key={bookingId}
                className="border border-error/30 bg-error/5 rounded-xl overflow-hidden"
              >
                <div className="p-4">
                  <div className="flex items-center gap-3">
                    <AlertTriangle className="w-5 h-5 text-error" />
                    <div>
                      <p className="font-medium text-error-dark">
                        {t('activity_error', { number: bookingIndex + 1 }) ||
                          `Activity ${bookingIndex + 1} - Error loading`}
                      </p>
                      <p className="text-sm text-error-dark/80">
                        {t('try_refresh') || 'Please try refreshing the page.'}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            );
          }

          if (!data) return null;

          // Add null safety for all data access
          const bookingInfo = data?.booking ?? null;
          const vouchers = data?.data ?? [];
          const canGenerate = data?.canGenerate ?? false;
          const completedCount = vouchers.filter((v) => v?.participant?.fullName).length;

          // Handle case where session is missing/expired (no booking info and no vouchers)
          if (!bookingInfo && vouchers.length === 0) {
            return (
              <div
                key={bookingId}
                className="border border-warning/30 bg-warning/5 rounded-xl overflow-hidden"
              >
                <div className="p-4">
                  <div className="flex items-center gap-3">
                    <AlertTriangle className="w-5 h-5 text-warning" />
                    <div>
                      <p className="font-medium text-warning-dark">
                        {t('session_expired') || 'Session Expired'}
                      </p>
                      <p className="text-sm text-warning-dark/80">
                        {t('session_expired_message') ||
                          'Your session may have expired. Please check your bookings.'}
                      </p>
                    </div>
                  </div>
                  <div className="mt-3">
                    <Link href="/dashboard/bookings">
                      <Button variant="outline" size="sm">
                        {tCommon('go_to_bookings') || 'Go to My Bookings'}
                      </Button>
                    </Link>
                  </div>
                </div>
              </div>
            );
          }

          return (
            <div
              key={bookingId}
              className="border border-neutral-200 rounded-xl overflow-hidden bg-white print:break-before-page"
            >
              {/* Accordion Header */}
              <button
                onClick={() => toggleAccordion(bookingId)}
                className="w-full p-4 flex items-center justify-between hover:bg-neutral-50 transition-colors"
              >
                <div className="flex items-center gap-3">
                  <div
                    className={`w-10 h-10 rounded-full flex items-center justify-center ${
                      canGenerate ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'
                    }`}
                  >
                    {canGenerate ? (
                      <CheckCircle className="w-5 h-5" />
                    ) : (
                      <AlertTriangle className="w-5 h-5" />
                    )}
                  </div>
                  <div className="text-left">
                    <p className="font-semibold text-neutral-900">
                      {getLocalizedString(
                        bookingInfo?.listingTitle,
                        t('activity') || `Activity ${bookingIndex + 1}`
                      )}
                    </p>
                    <p className="text-sm text-neutral-500">
                      {bookingInfo?.bookingNumber || bookingId.slice(0, 8)} • {vouchers.length}{' '}
                      {vouchers.length === 1
                        ? t('participant') || 'participant'
                        : t('participants') || 'participants'}
                    </p>
                  </div>
                </div>

                <div className="flex items-center gap-3">
                  {canGenerate && (
                    <span className="text-sm text-success font-medium hidden sm:inline">
                      {t('ready') || 'Ready'}
                    </span>
                  )}
                  <ChevronDown
                    className={`w-5 h-5 text-neutral-400 transition-transform ${
                      isExpanded ? 'rotate-180' : ''
                    }`}
                  />
                </div>
              </button>

              {/* Accordion Content */}
              {isExpanded && (
                <div className="border-t border-neutral-200">
                  {canGenerate && vouchers.length > 0 ? (
                    <div className="p-4 space-y-4">
                      {/* Voucher Cards */}
                      {vouchers.map((voucher, index) => (
                        <div
                          key={voucher.voucherCode}
                          className="bg-neutral-50 border border-neutral-200 rounded-lg overflow-hidden print:border-2 print:border-dashed print:mb-4 print:break-inside-avoid"
                        >
                          {/* Voucher Header */}
                          <div className="bg-primary text-white px-4 py-2 print:bg-neutral-200 print:text-neutral-900">
                            <div className="flex items-center justify-between">
                              <div className="flex items-center gap-2">
                                <Ticket className="w-4 h-4" />
                                <span className="font-bold text-sm">{voucher.voucherCode}</span>
                              </div>
                              <span className="text-xs opacity-80">
                                #{index + 1} / {vouchers.length}
                              </span>
                            </div>
                          </div>

                          {/* Voucher Body */}
                          <div className="p-4">
                            <div className="flex gap-4">
                              {/* QR Code */}
                              <div className="flex-shrink-0">
                                <div className="bg-white p-2 border border-neutral-200 rounded">
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
                                  {voucher.participant?.personType && (
                                    <span className="inline-block mt-1 text-xs bg-neutral-100 text-neutral-600 px-2 py-0.5 rounded">
                                      {voucher.participant.personType}
                                    </span>
                                  )}
                                </div>

                                {/* Event title */}
                                {voucher.event?.title && (
                                  <div>
                                    <p className="text-neutral-500">{t('event') || 'Event'}</p>
                                    <p className="font-medium text-neutral-900">
                                      {getLocalizedString(voucher.event.title)}
                                    </p>
                                  </div>
                                )}

                                <div className="flex gap-4">
                                  <div>
                                    <p className="text-neutral-500">{t('date') || 'Date'}</p>
                                    <p className="font-medium text-neutral-900">
                                      {voucher.event?.date || '-'}
                                    </p>
                                  </div>
                                  <div>
                                    <p className="text-neutral-500">{t('time') || 'Time'}</p>
                                    <p className="font-medium text-neutral-900">
                                      {voucher.event?.time || '-'}
                                    </p>
                                  </div>
                                </div>

                                {/* Event location */}
                                {voucher.event?.location && (
                                  <div>
                                    <p className="text-neutral-500">
                                      {t('location') || 'Location'}
                                    </p>
                                    <p className="font-medium text-neutral-900">
                                      {getLocalizedString(voucher.event.location)}
                                    </p>
                                  </div>
                                )}
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

                      {/* Per-activity download button */}
                      <div className="pt-2 print:hidden">
                        <Button
                          onClick={() => handleDownloadPdf(bookingId)}
                          variant="outline"
                          className="w-full"
                        >
                          <Download className="w-4 h-4 mr-2" />
                          {t('download_pdf') || 'Download PDF'}
                        </Button>
                      </div>
                    </div>
                  ) : (
                    <div className="p-6 text-center bg-warning/5">
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
              )}
            </div>
          );
        })}
      </div>

      {/* Download All Button */}
      {readyBookings > 0 && (
        <div className="bg-neutral-50 rounded-xl p-6 text-center print:hidden">
          <Button onClick={handlePrintAll} className="animate-pulse-glow" size="lg">
            <Download className="w-5 h-5 mr-2" />
            {t('download_all', { count: totalVouchers }) ||
              `Download All Vouchers (${totalVouchers})`}
          </Button>
          <p className="text-sm text-neutral-500 mt-3">
            {t('download_all_hint') || 'Downloads all vouchers as a single PDF'}
          </p>
        </div>
      )}

      {/* Back to bookings link */}
      <div className="mt-8 text-center print:hidden">
        <Link href="/dashboard/bookings">
          <Button variant="outline">{tCommon('go_to_bookings') || 'Go to My Bookings'}</Button>
        </Link>
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
