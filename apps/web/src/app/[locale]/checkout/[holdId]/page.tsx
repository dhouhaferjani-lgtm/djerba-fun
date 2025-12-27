'use client';

import { useEffect } from 'react';
import { useParams } from 'next/navigation';
import { useRouter, Link } from '@/i18n/navigation';
import { useTranslations } from 'next-intl';
import { MainLayout } from '@/components/templates/MainLayout';
import { BookingWizard } from '@/components/booking/BookingWizard';
import { useHold, useListingExtras } from '@/lib/api/hooks';
import { Button } from '@go-adventure/ui';
import { AlertTriangle, ArrowLeft, Clock } from 'lucide-react';
import { resolveTranslation } from '@/lib/utils/translate';
import { getListingUrl, getListingsIndexUrl } from '@/lib/utils/urls';
import type { Locale } from '@/i18n/routing';

export default function CheckoutPage() {
  const params = useParams();
  const router = useRouter();
  const locale = params?.locale as string;
  const holdId = params?.holdId as string;

  const t = useTranslations('common');
  const tCheckout = useTranslations('checkout');
  const tBooking = useTranslations('booking');

  const { data: holdData, isLoading, error, isError } = useHold(holdId);

  // Fetch available extras for the listing (only when we have hold data)
  const {
    data: extrasData,
    isLoading: isLoadingExtras,
    isError: isExtrasError,
  } = useListingExtras(
    holdData?.listing?.slug || '',
    {
      slotId: holdData?.slot?.id,
      personTypes: holdData?.personTypeBreakdown
        ? Object.keys(holdData.personTypeBreakdown)
        : undefined,
    },
    !!holdData?.listing?.slug && !!holdData?.slot?.id
  );

  // Log error if extras fail to load (non-critical)
  useEffect(() => {
    if (isExtrasError) {
      console.warn('Failed to load extras for listing:', holdData?.listing?.slug);
    }
  }, [isExtrasError, holdData?.listing?.slug]);

  // Handle expired hold - check for 410 status
  useEffect(() => {
    if (isError && error && 'status' in error && error.status === 410) {
      // Hold expired - handled in render
    }
  }, [isError, error]);

  if (isLoading || isLoadingExtras) {
    return (
      <MainLayout locale={locale}>
        <div className="min-h-[60vh] flex items-center justify-center">
          <div className="text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
            <p className="text-gray-600">{t('loading')}</p>
          </div>
        </div>
      </MainLayout>
    );
  }

  // Handle expired hold
  if (isError && error && 'status' in error && error.status === 410) {
    const errorData = (error as any).details;
    const listingSlug = errorData?.listingSlug;

    return (
      <MainLayout locale={locale}>
        <div className="min-h-[60vh] flex items-center justify-center">
          <div className="text-center max-w-md mx-auto px-4">
            <div className="mb-6">
              <Clock className="h-16 w-16 text-warning mx-auto" />
            </div>
            <h1 className="text-2xl font-bold text-gray-900 mb-3">
              {tCheckout('hold_expired_title') || 'Reservation Expired'}
            </h1>
            <p className="text-gray-600 mb-6">
              {tCheckout('hold_expired_message') ||
                'Your reservation has expired. Please select a new date and time to continue booking.'}
            </p>
            <div className="space-y-3">
              <Link href={getListingsIndexUrl(locale as Locale)}>
                <Button variant="primary" size="lg" className="w-full">
                  <ArrowLeft className="h-4 w-4 mr-2" />
                  {tCheckout('browse_listings') || 'Browse Listings'}
                </Button>
              </Link>
            </div>
          </div>
        </div>
      </MainLayout>
    );
  }

  // Handle not found or other errors
  if (isError || !holdData) {
    return (
      <MainLayout locale={locale}>
        <div className="min-h-[60vh] flex items-center justify-center">
          <div className="text-center max-w-md mx-auto px-4">
            <div className="mb-6">
              <AlertTriangle className="h-16 w-16 text-error mx-auto" />
            </div>
            <h1 className="text-2xl font-bold text-gray-900 mb-3">
              {tCheckout('hold_not_found_title') || 'Reservation Not Found'}
            </h1>
            <p className="text-gray-600 mb-6">
              {tCheckout('hold_not_found_message') ||
                'We could not find your reservation. It may have expired or been cancelled.'}
            </p>
            <Link href={getListingsIndexUrl(locale as Locale)}>
              <Button variant="primary" size="lg" className="w-full">
                <ArrowLeft className="h-4 w-4 mr-2" />
                {tCheckout('browse_listings') || 'Browse Listings'}
              </Button>
            </Link>
          </div>
        </div>
      </MainLayout>
    );
  }

  const { listing, slot } = holdData;

  // Ensure we have the required data
  if (!listing || !slot) {
    return (
      <MainLayout locale={locale}>
        <div className="min-h-[60vh] flex items-center justify-center">
          <div className="text-center max-w-md mx-auto px-4">
            <div className="mb-6">
              <AlertTriangle className="h-16 w-16 text-warning mx-auto" />
            </div>
            <h1 className="text-2xl font-bold text-gray-900 mb-3">{t('error')}</h1>
            <p className="text-gray-600 mb-6">
              {tCheckout('missing_data_message') ||
                'Some booking information is missing. Please try again.'}
            </p>
            <Link href={getListingsIndexUrl(locale as Locale)}>
              <Button variant="primary" size="lg" className="w-full">
                <ArrowLeft className="h-4 w-4 mr-2" />
                {tCheckout('browse_listings') || 'Browse Listings'}
              </Button>
            </Link>
          </div>
        </div>
      </MainLayout>
    );
  }

  const handleHoldExpired = () => {
    // Redirect to listing page when hold expires
    if (listing.slug && listing.location) {
      const listingUrl = getListingUrl(listing.slug, listing.location, locale as Locale);
      router.push(listingUrl);
    } else {
      router.push(getListingsIndexUrl(locale as Locale));
    }
  };

  const tr = (field: any) => resolveTranslation(field, locale);
  const title = tr(listing.title);

  return (
    <MainLayout locale={locale}>
      <div className="bg-gray-50 min-h-screen">
        {/* Header */}
        <div className="bg-white border-b border-gray-200">
          <div className="container mx-auto px-4 py-4">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-4">
                <Link
                  href={getListingUrl(listing.slug, listing.location, locale as Locale)}
                  className="flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors"
                >
                  <ArrowLeft className="h-5 w-5" />
                  <span className="hidden sm:inline">{tCheckout('back_to_listing') || 'Back'}</span>
                </Link>
                <div className="h-6 w-px bg-gray-300 hidden sm:block" />
                <h1 className="text-lg font-semibold text-gray-900 truncate max-w-xs sm:max-w-md">
                  {title}
                </h1>
              </div>
            </div>
          </div>
        </div>

        {/* Checkout Content */}
        <div className="container mx-auto px-4 py-8">
          <BookingWizard
            hold={holdData as any}
            listing={listing as any}
            slot={slot as any}
            availableExtras={extrasData || []}
            onExpired={handleHoldExpired}
          />
        </div>
      </div>
    </MainLayout>
  );
}
