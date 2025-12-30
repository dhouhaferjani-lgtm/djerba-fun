'use client';

import { useTranslations } from 'next-intl';
import { Link, useRouter } from '@/i18n/navigation';
import { useCurrentUser, useMyBookings } from '@/lib/api/hooks';
import { useEffect, useState } from 'react';
import { useLocale } from 'next-intl';
import { getListingsIndexUrl } from '@/lib/utils/urls';
import { ClaimBookingModal } from '@/components/booking/ClaimBookingModal';
import type { Locale } from '@/i18n/routing';

export default function DashboardPage() {
  const t = useTranslations('dashboard');
  const router = useRouter();
  const locale = useLocale();
  const { data: user, isLoading: isLoadingUser } = useCurrentUser();
  const { data: bookingsData, isLoading: isLoadingBookings, refetch } = useMyBookings();
  const [showClaimModal, setShowClaimModal] = useState(false);

  useEffect(() => {
    if (!isLoadingUser && !user) {
      router.push('/auth/login');
    }
  }, [user, isLoadingUser, router]);

  if (isLoadingUser || isLoadingBookings) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>
    );
  }

  const bookings = bookingsData?.data || [];
  const upcomingBookings = bookings.filter(
    (b) => b.status === 'confirmed' && b.startsAt && new Date(b.startsAt) > new Date()
  );
  const pastBookings = bookings.filter(
    (b) => b.status === 'completed' || (b.startsAt && new Date(b.startsAt) < new Date())
  );

  return (
    <div className="min-h-screen bg-gray-50 py-12">
      <div className="container mx-auto px-4">
        <div className="max-w-6xl mx-auto">
          {/* Header */}
          <div className="mb-8">
            <h1 className="text-3xl font-bold text-gray-900 mb-2">{t('title')}</h1>
            <p className="text-gray-600">
              {t('welcome')}, {user?.displayName}
            </p>
          </div>

          {/* Claim Booking Banner */}
          <div className="bg-gradient-to-r from-success-light to-primary/5 border border-success/20 rounded-lg p-6 mb-8">
            <div className="flex items-center justify-between gap-4">
              <div className="flex items-start gap-4">
                <div className="flex-shrink-0 w-12 h-12 bg-success-light rounded-lg flex items-center justify-center">
                  <svg
                    className="w-6 h-6 text-success"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                    />
                  </svg>
                </div>
                <div className="flex-1">
                  <h3 className="font-bold text-gray-900 mb-1">
                    {t('claim_booking_title') || 'Have past bookings?'}
                  </h3>
                  <p className="text-sm text-gray-700">
                    {t('claim_booking_subtitle') ||
                      'Link them to your account by entering your booking number!'}
                  </p>
                </div>
              </div>
              <button
                onClick={() => setShowClaimModal(true)}
                className="flex-shrink-0 px-6 py-3 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors"
              >
                {t('claim_booking_button') || 'Claim Booking'}
              </button>
            </div>
          </div>

          {/* Stats Grid */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600 mb-1">{t('total_bookings')}</p>
                  <p className="text-3xl font-bold text-gray-900">{bookings.length}</p>
                </div>
                <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                  <svg
                    className="w-6 h-6 text-primary"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                    />
                  </svg>
                </div>
              </div>
            </div>

            <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600 mb-1">{t('upcoming')}</p>
                  <p className="text-3xl font-bold text-gray-900">{upcomingBookings.length}</p>
                </div>
                <div className="w-12 h-12 bg-success-light rounded-lg flex items-center justify-center">
                  <svg
                    className="w-6 h-6 text-success"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                  </svg>
                </div>
              </div>
            </div>

            <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600 mb-1">{t('past')}</p>
                  <p className="text-3xl font-bold text-gray-900">{pastBookings.length}</p>
                </div>
                <div className="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                  <svg
                    className="w-6 h-6 text-gray-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                  </svg>
                </div>
              </div>
            </div>
          </div>

          {/* Quick Actions */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <Link
              href="/dashboard/bookings"
              className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow"
            >
              <div className="flex items-center gap-4">
                <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                  <svg
                    className="w-6 h-6 text-primary"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                    />
                  </svg>
                </div>
                <div>
                  <h3 className="font-semibold text-gray-900">{t('my_bookings')}</h3>
                  <p className="text-sm text-gray-600">{t('view_all_bookings')}</p>
                </div>
              </div>
            </Link>

            <Link
              href="/dashboard/profile"
              className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow"
            >
              <div className="flex items-center gap-4">
                <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                  <svg
                    className="w-6 h-6 text-primary"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                    />
                  </svg>
                </div>
                <div>
                  <h3 className="font-semibold text-gray-900">{t('my_profile')}</h3>
                  <p className="text-sm text-gray-600">{t('manage_your_profile')}</p>
                </div>
              </div>
            </Link>

            <Link
              href={getListingsIndexUrl(locale as Locale)}
              className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow"
            >
              <div className="flex items-center gap-4">
                <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
                  <svg
                    className="w-6 h-6 text-primary"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                    />
                  </svg>
                </div>
                <div>
                  <h3 className="font-semibold text-gray-900">{t('explore_activities')}</h3>
                  <p className="text-sm text-gray-600">{t('find_new_adventures')}</p>
                </div>
              </div>
            </Link>
          </div>

          {/* Upcoming Bookings */}
          {upcomingBookings.length > 0 && (
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h2 className="text-xl font-bold text-gray-900 mb-4">{t('upcoming_bookings')}</h2>
              <div className="space-y-4">
                {upcomingBookings.slice(0, 3).map((booking) => (
                  <Link
                    key={booking.id}
                    href={`/dashboard/bookings/${booking.id}`}
                    className="block p-4 border border-gray-200 rounded-lg hover:border-primary hover:bg-primary/5 transition-all"
                  >
                    <div className="flex items-start justify-between">
                      <div>
                        <h3 className="font-semibold text-gray-900">
                          {t('booking')} #{booking.code}
                        </h3>
                        <p className="text-sm text-gray-600 mt-1">
                          {booking.startsAt ? new Date(booking.startsAt).toLocaleDateString() : '-'}
                        </p>
                      </div>
                      <span className="px-3 py-1 bg-success-light text-success-dark text-xs font-medium rounded-full">
                        {booking.status}
                      </span>
                    </div>
                  </Link>
                ))}
              </div>
              {upcomingBookings.length > 3 && (
                <Link
                  href="/dashboard/bookings"
                  className="block mt-4 text-center text-primary hover:text-primary/80 font-medium"
                >
                  {t('view_all')} →
                </Link>
              )}
            </div>
          )}

          {/* No Bookings State */}
          {bookings.length === 0 && (
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
              <svg
                className="w-16 h-16 text-gray-400 mx-auto mb-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                />
              </svg>
              <h3 className="text-lg font-semibold text-gray-900 mb-2">{t('no_bookings')}</h3>
              <p className="text-gray-600 mb-6">{t('no_bookings_message')}</p>
              <Link
                href="/listings"
                className="inline-block px-6 py-3 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition-colors"
              >
                {t('start_exploring')}
              </Link>
            </div>
          )}
        </div>
      </div>

      {/* Claim Booking Modal */}
      {showClaimModal && (
        <ClaimBookingModal
          onClose={() => setShowClaimModal(false)}
          onSuccess={() => {
            refetch();
          }}
        />
      )}
    </div>
  );
}
