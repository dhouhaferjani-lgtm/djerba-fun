'use client';

import { useEffect, useState, useRef } from 'react';
import { useParams } from 'next/navigation';
import { useRouter, Link } from '@/i18n/navigation';
import { useTranslations } from 'next-intl';
import { BookingWizard, type SelectedExtra } from '@/components/booking/BookingWizard';
import { BookingSummary } from '@/components/booking/BookingSummary';
import { CheckoutAuthModal } from '@/components/booking/CheckoutAuthModal';
import { useHold, useListingExtras } from '@/lib/api/hooks';
import { useAuth } from '@/lib/contexts/AuthContext';
import { Button } from '@go-adventure/ui';
import { AlertTriangle, ArrowLeft, Clock } from 'lucide-react';
import { resolveTranslation } from '@/lib/utils/translate';
import { getListingUrl, getListingsIndexUrl } from '@/lib/utils/urls';

export default function CheckoutPage() {
  const params = useParams();
  const router = useRouter();
  const locale = params?.locale as string;
  const holdId = params?.holdId as string;

  const t = useTranslations('common');
  const tCheckout = useTranslations('checkout');
  const tBooking = useTranslations('booking');

  const { user, isLoading: isAuthLoading } = useAuth();
  const [showAuthModal, setShowAuthModal] = useState(false);
  const [hasChosenAuthMethod, setHasChosenAuthMethod] = useState(false);
  const [selectedExtras, setSelectedExtras] = useState<SelectedExtra[]>([]);
  const hasInitializedExtras = useRef(false);

  const { data: holdData, isLoading, error, isError } = useHold(holdId);

  // Show auth modal if user is not logged in and hasn't chosen a method yet
  useEffect(() => {
    if (!isAuthLoading && !user && !hasChosenAuthMethod && holdData) {
      setShowAuthModal(true);
    }
  }, [isAuthLoading, user, hasChosenAuthMethod, holdData]);

  // Load extras from hold data (backend) OR sessionStorage (fallback)
  // Uses ref to ensure we only initialize once and don't override user changes on refetch
  useEffect(() => {
    if (holdId && holdData && !hasInitializedExtras.current) {
      // First try to get from hold data (persisted in database)
      if (holdData.extras && Array.isArray(holdData.extras) && holdData.extras.length > 0) {
        setSelectedExtras(holdData.extras);
        hasInitializedExtras.current = true;
        // Clean up sessionStorage if it exists (no longer needed)
        sessionStorage.removeItem(`checkout-extras-${holdId}`);
        return;
      }

      // Fallback to sessionStorage (for backwards compatibility)
      const storedExtras = sessionStorage.getItem(`checkout-extras-${holdId}`);
      if (storedExtras) {
        try {
          const parsed = JSON.parse(storedExtras);
          setSelectedExtras(parsed);
          // Clean up after reading
          sessionStorage.removeItem(`checkout-extras-${holdId}`);
        } catch (e) {
          console.warn('Failed to parse stored extras');
        }
      }
      hasInitializedExtras.current = true;
    }
  }, [holdId, holdData]);

  // Fetch available extras for the listing (only when we have hold data)
  const {
    data: extrasData,
    isLoading: isLoadingExtras,
    isError: isExtrasError,
  } = useListingExtras(
    holdData?.listing?.slug || '',
    {
      slotId: holdData?.slot?.id?.toString(),
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
      <div className="min-h-[60vh] flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
          <p className="text-gray-600">{t('loading')}</p>
        </div>
      </div>
    );
  }

  // Handle expired hold
  if (isError && error && 'status' in error && error.status === 410) {
    const errorData = (error as any).details;
    const listingSlug = errorData?.listingSlug;

    return (
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
            <Link href={getListingsIndexUrl('fr')}>
              <Button variant="primary" size="lg" className="w-full">
                <ArrowLeft className="h-4 w-4 mr-2" />
                {tCheckout('browse_listings') || 'Browse Listings'}
              </Button>
            </Link>
          </div>
        </div>
      </div>
    );
  }

  // Handle not found or other errors
  if (isError || !holdData) {
    return (
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
          <Link href={getListingsIndexUrl('fr')}>
            <Button variant="primary" size="lg" className="w-full">
              <ArrowLeft className="h-4 w-4 mr-2" />
              {tCheckout('browse_listings') || 'Browse Listings'}
            </Button>
          </Link>
        </div>
      </div>
    );
  }

  const { listing, slot } = holdData;

  // Ensure we have the required data
  if (!listing || !slot) {
    return (
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
          <Link href={getListingsIndexUrl('fr')}>
            <Button variant="primary" size="lg" className="w-full">
              <ArrowLeft className="h-4 w-4 mr-2" />
              {tCheckout('browse_listings') || 'Browse Listings'}
            </Button>
          </Link>
        </div>
      </div>
    );
  }

  const handleHoldExpired = () => {
    // Redirect to listing page when hold expires
    if (listing.slug && listing.location) {
      const listingUrl = getListingUrl(listing.slug, listing.location, 'fr');
      router.push(listingUrl);
    } else {
      router.push(getListingsIndexUrl('fr'));
    }
  };

  // Auth modal handlers
  const handleGuestCheckout = () => {
    setHasChosenAuthMethod(true);
    setShowAuthModal(false);
  };

  const handleEmailLogin = () => {
    // Redirect to login page with return URL
    router.push(`/auth/login?returnUrl=/checkout/${holdId}` as any);
  };

  const handleCreateAccount = () => {
    // Redirect to register page with return URL
    router.push(`/auth/register?returnUrl=/checkout/${holdId}` as any);
  };

  const tr = (field: any) => resolveTranslation(field, locale);
  const title = tr(listing.title);

  return (
    <>
      <div className="bg-gray-50 min-h-screen">
        {/* Header */}
        <div className="bg-white border-b border-gray-200">
          <div className="container mx-auto px-4 py-4">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-4">
                <Link
                  href={getListingUrl(listing.slug, listing.location, 'fr')}
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

        {/* Checkout Content - Two Column Layout */}
        <div className="container mx-auto px-4 py-8">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {/* Left Column - Booking Form (2/3 width on large screens) */}
            <div className="lg:col-span-2">
              <BookingWizard
                hold={holdData as any}
                listing={listing as any}
                slot={slot as any}
                availableExtras={extrasData || []}
                initialExtras={selectedExtras}
                onExpired={handleHoldExpired}
                onExtrasChange={setSelectedExtras}
              />
            </div>

            {/* Right Column - Booking Summary (1/3 width on large screens, sticky) */}
            <div className="lg:col-span-1">
              <div className="sticky top-24">
                <BookingSummary
                  hold={holdData as any}
                  listing={listing as any}
                  slot={slot as any}
                  selectedExtras={selectedExtras.map((sel) => {
                    const extra = extrasData?.find((e) => e.id === sel.id);
                    const price =
                      extra?.displayPrice ??
                      (slot.currency === 'TND' ? extra?.priceTnd : extra?.priceEur) ??
                      0;
                    return {
                      id: sel.id,
                      name: extra?.name || '',
                      quantity: sel.quantity,
                      price,
                    };
                  })}
                  currency={slot.currency}
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Auth Modal */}
      <CheckoutAuthModal
        isOpen={showAuthModal}
        onClose={() => setShowAuthModal(false)}
        onGuestCheckout={handleGuestCheckout}
        onEmailLogin={handleEmailLogin}
        onCreateAccount={handleCreateAccount}
      />
    </>
  );
}
