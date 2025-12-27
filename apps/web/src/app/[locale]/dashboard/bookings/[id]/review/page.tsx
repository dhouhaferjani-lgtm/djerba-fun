'use client';

import { useParams, useRouter } from 'next/navigation';
import { useTranslations, useLocale } from 'next-intl';
import { useBooking, useCreateReview } from '@/lib/api/hooks';
import ReviewForm from '@/components/reviews/ReviewForm';
import Link from 'next/link';
import type { CreateReviewRequest } from '@go-adventure/schemas';
import { format } from 'date-fns';
import { enUS, fr } from 'date-fns/locale';

export default function ReviewSubmissionPage() {
  const params = useParams();
  const router = useRouter();
  const bookingId = params.id as string;
  const locale = useLocale();
  const t = useTranslations('reviews');
  const tDashboard = useTranslations('dashboard');
  const dateLocale = locale === 'fr' ? fr : enUS;

  const { data: bookingData, isLoading: isLoadingBooking } = useBooking(bookingId);
  const createReview = useCreateReview();

  const handleSubmit = async (data: CreateReviewRequest) => {
    try {
      await createReview.mutateAsync({ bookingId, request: data });
      router.push(`/${locale}/dashboard/bookings/${bookingId}`);
    } catch (error) {
      console.error('Failed to submit review:', error);
    }
  };

  if (isLoadingBooking) {
    return (
      <div className="min-h-screen bg-gray-50 py-12">
        <div className="container mx-auto px-4 max-w-3xl">
          <div className="bg-white rounded-lg shadow-sm p-8 animate-pulse">
            <div className="h-8 bg-gray-200 rounded w-64 mb-4" />
            <div className="h-4 bg-gray-200 rounded w-full mb-8" />
            <div className="space-y-4">
              <div className="h-24 bg-gray-200 rounded" />
              <div className="h-24 bg-gray-200 rounded" />
              <div className="h-48 bg-gray-200 rounded" />
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (!bookingData) {
    return (
      <div className="min-h-screen bg-gray-50 py-12">
        <div className="container mx-auto px-4 max-w-3xl">
          <div className="bg-white rounded-lg shadow-sm p-8 text-center">
            <h1 className="text-2xl font-bold text-gray-900 mb-2">Booking Not Found</h1>
            <p className="text-gray-600 mb-4">
              The booking you&apos;re trying to review doesn&apos;t exist.
            </p>
            <Link href={`/${locale}/dashboard/bookings`} className="text-primary hover:underline">
              {tDashboard('back_to_bookings')}
            </Link>
          </div>
        </div>
      </div>
    );
  }

  const booking = bookingData;

  // Check if booking is completed
  if (booking.status !== 'completed') {
    return (
      <div className="min-h-screen bg-gray-50 py-12">
        <div className="container mx-auto px-4 max-w-3xl">
          <div className="bg-white rounded-lg shadow-sm p-8 text-center">
            <svg
              className="w-16 h-16 mx-auto text-gray-400 mb-4"
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
            <h1 className="text-2xl font-bold text-gray-900 mb-2">Cannot Review Yet</h1>
            <p className="text-gray-600 mb-4">
              You can only review bookings that have been completed.
            </p>
            <Link
              href={`/${locale}/dashboard/bookings/${bookingId}`}
              className="text-primary hover:underline"
            >
              View Booking
            </Link>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 py-12">
      <div className="container mx-auto px-4 max-w-3xl">
        {/* Back Button */}
        <Link
          href={`/${locale}/dashboard/bookings/${bookingId}`}
          className="inline-flex items-center gap-2 text-gray-600 hover:text-primary mb-6 transition-colors"
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M15 19l-7-7 7-7"
            />
          </svg>
          Back to Booking
        </Link>

        {/* Main Content */}
        <div className="bg-white rounded-lg shadow-sm p-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">{t('write_review')}</h1>
          <p className="text-gray-600 mb-8">
            Share your experience to help other travelers make informed decisions
          </p>

          {/* Booking Summary */}
          <div className="bg-gray-50 rounded-lg p-6 mb-8">
            <h2 className="font-semibold text-gray-900 mb-4">Booking Summary</h2>
            <div className="space-y-3">
              <div className="flex justify-between">
                <span className="text-gray-600">Booking Number</span>
                <span className="font-medium text-gray-900">{booking.code}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Date</span>
                <span className="font-medium text-gray-900">
                  {booking.startsAt
                    ? format(new Date(booking.startsAt), 'PPP', { locale: dateLocale })
                    : '-'}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Guests</span>
                <span className="font-medium text-gray-900">
                  {booking.guests ?? booking.quantity}
                </span>
              </div>
            </div>
          </div>

          {/* Review Form */}
          <ReviewForm onSubmit={handleSubmit} isSubmitting={createReview.isPending} />

          {/* Error Message */}
          {createReview.isError && (
            <div className="mt-4 p-4 bg-error-light border border-error rounded-lg">
              <p className="text-error-dark text-sm">Failed to submit review. Please try again.</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
