'use client';

import { Suspense, useEffect, useState } from 'react';
import { useSearchParams } from 'next/navigation';
import { useRouter } from '@/i18n/navigation';
import { useTranslations } from 'next-intl';
import { BookingWizard } from '@/components/booking/BookingWizard';
import { useCurrentUser } from '@/lib/api/hooks';
import type { BookingHold, ListingSummary, AvailabilitySlot } from '@go-adventure/schemas';

function CheckoutContent() {
  const t = useTranslations('common');
  const searchParams = useSearchParams();
  const router = useRouter();
  const { data: user, isLoading: isLoadingUser } = useCurrentUser();

  const [hold] = useState<BookingHold | null>(null);
  const [listing] = useState<ListingSummary | null>(null);
  const [slot] = useState<AvailabilitySlot | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    // Check authentication
    if (!isLoadingUser && !user) {
      const loginUrl = '/auth/login';
      router.push(loginUrl);
      return;
    }

    // Get hold ID from query params
    const holdIdParam = searchParams.get('holdId');
    if (!holdIdParam) {
      router.push('/');
      return;
    }

    // In a real implementation, we would fetch the hold, listing, and slot data
    // For now, we'll use mock data or expect it to be passed via state
    const fetchCheckoutData = async () => {
      try {
        // TODO: Implement API call to fetch hold details
        // const response = await holdsApi.getById(holdIdParam);
        // setHold(response.data.hold);
        // setListing(response.data.listing);
        // setSlot(response.data.slot);

        setIsLoading(false);
      } catch (error) {
        console.error('Failed to load checkout data:', error);
        router.push('/');
      }
    };

    if (!isLoadingUser && user) {
      fetchCheckoutData();
    }
  }, [user, isLoadingUser, router, searchParams]);

  const handleHoldExpired = () => {
    router.push('/');
  };

  if (isLoadingUser || isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
          <p className="text-gray-600">{t('loading')}</p>
        </div>
      </div>
    );
  }

  if (!hold || !listing || !slot) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <p className="text-gray-600">{t('error')}</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 py-12">
      <div className="container mx-auto px-4">
        <BookingWizard
          hold={hold}
          listing={listing}
          slot={slot}
          availableExtras={[]}
          onExpired={handleHoldExpired}
        />
      </div>
    </div>
  );
}

export default function CheckoutPage() {
  return (
    <Suspense
      fallback={
        <div className="min-h-screen flex items-center justify-center">
          <div className="text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
            <p className="text-gray-600">Loading...</p>
          </div>
        </div>
      }
    >
      <CheckoutContent />
    </Suspense>
  );
}
