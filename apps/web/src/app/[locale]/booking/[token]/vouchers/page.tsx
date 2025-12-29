'use client';

import { useEffect, useState } from 'react';
import { useParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { Link } from '@/i18n/navigation';
import {
  magicLinksApi,
  type MagicLinkVouchersResponse,
  type MagicLinkVoucher,
} from '@/lib/api/client';
import { QRCodeSVG } from 'qrcode.react';
import html2canvas from 'html2canvas';
import { jsPDF } from 'jspdf';

export default function MagicLinkVouchersPage() {
  const params = useParams();
  const token = params.token as string;
  const t = useTranslations('vouchers');
  const tCommon = useTranslations('common');

  const [vouchersData, setVouchersData] = useState<MagicLinkVouchersResponse | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchVouchers = async () => {
      try {
        const response = await magicLinksApi.getVouchers(token);
        setVouchersData(response);
      } catch (err: unknown) {
        const apiError = err as { status?: number; message?: string };
        if (apiError.status === 410 || apiError.status === 404) {
          setError('This link is invalid or has expired.');
        } else {
          setError(apiError.message || 'Failed to load vouchers.');
        }
      } finally {
        setIsLoading(false);
      }
    };

    fetchVouchers();
  }, [token]);

  const handlePrint = () => {
    window.print();
  };

  const handleDownloadVoucher = async (voucherCode: string, participantName: string) => {
    const element = document.getElementById(`voucher-${voucherCode}`);
    if (!element) return;

    try {
      // Create canvas from the voucher element
      const canvas = await html2canvas(element, {
        scale: 2,
        logging: false,
        useCORS: true,
        backgroundColor: '#ffffff',
      });

      // Calculate PDF dimensions (A4 size)
      const imgWidth = 210; // A4 width in mm
      const imgHeight = (canvas.height * imgWidth) / canvas.width;

      // Create PDF
      const pdf = new jsPDF({
        orientation: imgHeight > imgWidth ? 'portrait' : 'landscape',
        unit: 'mm',
        format: 'a4',
      });

      const imgData = canvas.toDataURL('image/png');
      pdf.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);

      // Download with participant name in filename
      const fileName = `voucher-${participantName.replace(/\s+/g, '-')}-${voucherCode}.pdf`;
      pdf.save(fileName);
    } catch (error) {
      console.error('Error generating PDF:', error);
    }
  };

  if (isLoading) {
    return (
      <div className="max-w-4xl mx-auto p-6">
        <div className="animate-pulse space-y-4">
          <div className="h-8 bg-gray-200 rounded w-1/3"></div>
          <div className="h-4 bg-gray-200 rounded w-2/3"></div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
            {[1, 2].map((i) => (
              <div key={i} className="h-64 bg-gray-200 rounded"></div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="max-w-4xl mx-auto p-6">
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
          <h2 className="text-xl font-bold text-gray-900 mb-2">{error}</h2>
          <div className="space-y-3 mt-6">
            <Link
              href="/booking/recover"
              className="block w-full px-6 py-3 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors text-center"
            >
              Request New Link
            </Link>
          </div>
        </div>
      </div>
    );
  }

  // Vouchers not ready yet
  if (!vouchersData?.canGenerate) {
    return (
      <div className="max-w-4xl mx-auto p-6">
        <div className="mb-8">
          <Link
            href={`/booking/${token}`}
            className="text-sm text-gray-600 hover:text-gray-900 mb-4 inline-flex items-center"
          >
            <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M15 19l-7-7 7-7"
              />
            </svg>
            {tCommon('back') || 'Back'}
          </Link>
          <h1 className="text-2xl font-bold text-gray-900 mt-2">{t('title') || 'Your Vouchers'}</h1>
        </div>

        <div className="bg-warning-light border border-warning rounded-lg p-6 text-center">
          <div className="w-16 h-16 bg-warning-light rounded-full flex items-center justify-center mx-auto mb-4">
            <svg
              className="w-8 h-8 text-warning-dark"
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
          <h2 className="text-lg font-semibold text-warning-dark mb-2">
            {t('vouchers_not_ready') || 'Vouchers Not Ready Yet'}
          </h2>
          <p className="text-warning-dark mb-4">
            {vouchersData?.message ||
              t('complete_names_first') ||
              'Please enter all participant names before downloading vouchers.'}
          </p>
          <Link
            href={`/booking/${token}/participants`}
            className="inline-flex items-center px-4 py-2 bg-warning-dark text-white rounded-lg font-medium hover:bg-warning-dark/90 transition-colors"
          >
            {t('enter_names') || 'Enter Participant Names'}
          </Link>
        </div>
      </div>
    );
  }

  const vouchers = vouchersData.data || [];
  const bookingInfo = vouchersData.booking;

  return (
    <div className="max-w-4xl mx-auto p-6" id="vouchers-container">
      {/* Header - hidden when printing */}
      <div className="mb-8 print:hidden">
        <Link
          href={`/booking/${token}`}
          className="text-sm text-gray-600 hover:text-gray-900 mb-4 inline-flex items-center"
        >
          <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M15 19l-7-7 7-7"
            />
          </svg>
          {tCommon('back') || 'Back'}
        </Link>
        <div className="flex items-center justify-between mt-2">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">{t('title') || 'Your Vouchers'}</h1>
            <p className="text-gray-600 mt-1">
              {t('subtitle') ||
                'Present these vouchers at check-in. Each participant needs their own voucher.'}
            </p>
          </div>
          <button
            onClick={handlePrint}
            className="px-4 py-2 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors flex items-center gap-2"
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
              />
            </svg>
            {t('print_all') || 'Print All'}
          </button>
        </div>
      </div>

      {/* Booking Info */}
      {bookingInfo && (
        <div className="bg-gray-50 rounded-lg p-4 mb-6 print:hidden">
          <div className="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
            <div>
              <p className="text-gray-600">{t('booking_number') || 'Booking'}</p>
              <p className="font-semibold text-gray-900">{bookingInfo.bookingNumber}</p>
            </div>
            <div>
              <p className="text-gray-600">{t('activity') || 'Activity'}</p>
              <p className="font-semibold text-gray-900">{bookingInfo.listingTitle}</p>
            </div>
            <div>
              <p className="text-gray-600">{t('participants') || 'Participants'}</p>
              <p className="font-semibold text-gray-900">{vouchers.length}</p>
            </div>
          </div>
        </div>
      )}

      {/* Vouchers Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6 print:grid-cols-1 print:gap-0">
        {vouchers.map((voucher: MagicLinkVoucher, index: number) => (
          <div key={voucher.voucherCode} className="space-y-2">
            {/* Download Button - hidden when printing */}
            <button
              onClick={() =>
                handleDownloadVoucher(
                  voucher.voucherCode,
                  voucher.participant.fullName || `Participant-${index + 1}`
                )
              }
              className="w-full px-4 py-2 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors flex items-center justify-center gap-2 print:hidden"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                />
              </svg>
              Download Voucher
            </button>

            <div
              id={`voucher-${voucher.voucherCode}`}
              className="bg-white border border-gray-200 rounded-lg overflow-hidden print:border-2 print:border-dashed print:mb-8 print:break-inside-avoid"
            >
              {/* Voucher Header */}
              <div className="bg-primary text-white p-4 print:bg-gray-100 print:text-gray-900">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm opacity-80 print:opacity-100">
                      {t('voucher') || 'Voucher'}
                    </p>
                    <p className="font-bold text-lg">{voucher.voucherCode}</p>
                  </div>
                  <div className="text-right">
                    <p className="text-sm opacity-80 print:opacity-100">#{index + 1}</p>
                  </div>
                </div>
              </div>

              {/* Voucher Body */}
              <div className="p-6">
                <div className="flex gap-6">
                  {/* QR Code */}
                  <div className="flex-shrink-0">
                    <div className="bg-white p-2 border border-gray-200 rounded-lg">
                      <QRCodeSVG value={voucher.qrCodeData} size={120} level="M" />
                    </div>
                  </div>

                  {/* Details */}
                  <div className="flex-1 space-y-3">
                    <div>
                      <p className="text-sm text-gray-600">{t('participant') || 'Participant'}</p>
                      <p className="font-semibold text-gray-900 text-lg">
                        {voucher.participant.fullName ||
                          t('name_not_entered') ||
                          'Name not entered'}
                      </p>
                      {voucher.participant.personType && (
                        <span className="inline-block mt-1 text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                          {voucher.participant.personType}
                        </span>
                      )}
                    </div>

                    <div>
                      <p className="text-sm text-gray-600">{t('event') || 'Event'}</p>
                      <p className="font-medium text-gray-900">{voucher.event.title}</p>
                    </div>

                    <div className="flex gap-4 text-sm">
                      <div>
                        <p className="text-gray-600">{t('date') || 'Date'}</p>
                        <p className="font-medium text-gray-900">{voucher.event.date}</p>
                      </div>
                      <div>
                        <p className="text-gray-600">{t('time') || 'Time'}</p>
                        <p className="font-medium text-gray-900">{voucher.event.time}</p>
                      </div>
                    </div>

                    {voucher.event.location && (
                      <div className="text-sm">
                        <p className="text-gray-600">{t('location') || 'Location'}</p>
                        <p className="font-medium text-gray-900">{voucher.event.location}</p>
                      </div>
                    )}
                  </div>
                </div>

                {/* Check-in Status */}
                {voucher.participant.checkedIn && (
                  <div className="mt-4 p-3 bg-success-light border border-success rounded-lg flex items-center gap-2">
                    <svg
                      className="w-5 h-5 text-success-dark"
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
                    <span className="text-sm font-medium text-success-dark">
                      {t('checked_in') || 'Checked In'}
                    </span>
                  </div>
                )}
              </div>

              {/* Voucher Footer */}
              <div className="px-6 py-3 bg-gray-50 border-t text-center">
                <p className="text-xs text-gray-500">
                  {t('scan_instruction') || 'Present this QR code at check-in'}
                </p>
              </div>
            </div>
          </div>
        ))}
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
