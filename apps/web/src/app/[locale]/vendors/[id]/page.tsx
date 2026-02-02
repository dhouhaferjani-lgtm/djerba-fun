'use client';

import { useState } from 'react';
import { useParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { useVendorProfile, useVendorListings } from '@/lib/api/hooks';
import { format } from 'date-fns';
import { enUS, fr } from 'date-fns/locale';
import { useLocale } from 'next-intl';
import Link from 'next/link';
import { getListingUrl } from '@/lib/utils/urls';
import { normalizeMediaUrl } from '@/lib/utils/image';
import type { Locale } from '@/i18n/routing';

type Tab = 'listings' | 'reviews';

export default function VendorProfilePage() {
  const params = useParams();
  const vendorId = params.id as string;
  const t = useTranslations('vendor');
  const locale = useLocale();
  const dateLocale = locale === 'fr' ? fr : enUS;

  const [activeTab, setActiveTab] = useState<Tab>('listings');

  const { data: profileData, isLoading: isLoadingProfile } = useVendorProfile(vendorId);
  const { data: listingsData, isLoading: isLoadingListings } = useVendorListings(vendorId);

  if (isLoadingProfile) {
    return (
      <div className="min-h-screen bg-gray-50">
        {/* Cover Image Skeleton */}
        <div className="w-full h-64 bg-gray-200 animate-pulse" />

        {/* Profile Content Skeleton */}
        <div className="container mx-auto px-4 -mt-24">
          <div className="bg-white rounded-lg shadow-lg p-8">
            <div className="flex items-start gap-6">
              <div className="w-32 h-32 rounded-lg bg-gray-200 animate-pulse" />
              <div className="flex-1 space-y-3">
                <div className="h-8 bg-gray-200 rounded w-64 animate-pulse" />
                <div className="h-4 bg-gray-200 rounded w-48 animate-pulse" />
                <div className="h-4 bg-gray-200 rounded w-full animate-pulse" />
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (!profileData?.data) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <h1 className="text-2xl font-bold text-gray-900 mb-2">Vendor Not Found</h1>
          <p className="text-gray-600">
            The vendor profile you&apos;re looking for doesn&apos;t exist.
          </p>
        </div>
      </div>
    );
  }

  const vendor = profileData.data;
  const listings = listingsData?.data || [];
  const memberSinceDate = format(new Date(vendor.memberSince), 'MMMM yyyy', {
    locale: dateLocale,
  });

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Cover Image */}
      <div
        className="w-full h-64 bg-gradient-to-br from-primary to-primary-light"
        style={
          vendor.coverImageUrl
            ? {
                backgroundImage: `url(${vendor.coverImageUrl})`,
                backgroundSize: 'cover',
                backgroundPosition: 'center',
              }
            : {}
        }
      />

      {/* Profile Content */}
      <div className="container mx-auto px-4 -mt-24 pb-12">
        <div className="bg-white rounded-lg shadow-lg p-8">
          {/* Profile Header */}
          <div className="flex flex-col md:flex-row items-start gap-6 mb-8">
            {/* Logo */}
            <div className="w-32 h-32 bg-white rounded-lg shadow-md flex items-center justify-center overflow-hidden border-4 border-white">
              {vendor.logoUrl ? (
                <img
                  src={vendor.logoUrl}
                  alt={vendor.companyName}
                  className="w-full h-full object-cover"
                />
              ) : (
                <div className="w-full h-full bg-primary text-white flex items-center justify-center text-4xl font-bold">
                  {vendor.companyName.charAt(0)}
                </div>
              )}
            </div>

            {/* Vendor Info */}
            <div className="flex-1">
              <div className="flex items-start justify-between mb-3">
                <div>
                  <h1 className="text-3xl font-bold text-gray-900 mb-2">{vendor.companyName}</h1>
                  {vendor.verificationBadges && vendor.verificationBadges.length > 0 && (
                    <div className="flex items-center gap-2 mb-2">
                      {vendor.verificationBadges.map((badge: string) => (
                        <span
                          key={badge}
                          className="inline-flex items-center gap-1 px-3 py-1 bg-success-light text-success-dark rounded-full text-sm font-medium"
                        >
                          <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path
                              fillRule="evenodd"
                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                              clipRule="evenodd"
                            />
                          </svg>
                          {t('verified')}
                        </span>
                      ))}
                    </div>
                  )}
                </div>
              </div>

              {/* Stats */}
              <div className="flex flex-wrap gap-6 mb-4">
                {vendor.rating && (
                  <div className="flex items-center gap-2">
                    <div className="flex items-center">
                      <svg className="w-5 h-5 text-secondary fill-secondary" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                      </svg>
                      <span className="ml-1 font-semibold text-gray-900">
                        {vendor.rating.toFixed(1)}
                      </span>
                    </div>
                    <span className="text-gray-600">({vendor.reviewsCount} reviews)</span>
                  </div>
                )}
                <div className="text-gray-600">
                  <span className="font-semibold text-gray-900">{vendor.listingsCount}</span>{' '}
                  {t('listings')}
                </div>
                <div className="text-gray-600">
                  {t('member_since')}{' '}
                  <span className="font-semibold text-gray-900">{memberSinceDate}</span>
                </div>
              </div>

              {/* Description */}
              {vendor.description && (
                <p className="text-gray-700 leading-relaxed">{vendor.description}</p>
              )}
            </div>
          </div>

          {/* Tabs */}
          <div className="border-b border-gray-200 mb-8">
            <div className="flex gap-8">
              <button
                onClick={() => setActiveTab('listings')}
                className={`pb-4 px-2 font-semibold transition-colors border-b-2 ${
                  activeTab === 'listings'
                    ? 'border-primary text-primary'
                    : 'border-transparent text-gray-500 hover:text-gray-700'
                }`}
              >
                {t('listings')}
              </button>
              <button
                onClick={() => setActiveTab('reviews')}
                className={`pb-4 px-2 font-semibold transition-colors border-b-2 ${
                  activeTab === 'reviews'
                    ? 'border-primary text-primary'
                    : 'border-transparent text-gray-500 hover:text-gray-700'
                }`}
              >
                Reviews
              </button>
            </div>
          </div>

          {/* Tab Content */}
          {activeTab === 'listings' && (
            <div>
              {isLoadingListings ? (
                <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                  {[1, 2, 3].map((i) => (
                    <div key={i} className="bg-gray-100 rounded-lg h-80 animate-pulse" />
                  ))}
                </div>
              ) : listings.length > 0 ? (
                <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                  {listings.map((listing) => (
                    <Link
                      key={listing.id}
                      href={getListingUrl(listing.slug, listing.location, locale as Locale) as any}
                      className="group bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow"
                    >
                      {/* Image */}
                      <div className="aspect-video bg-gray-200 overflow-hidden">
                        {listing.media[0] && (
                          <img
                            src={normalizeMediaUrl(listing.media[0].url)}
                            alt={listing.media[0].alt}
                            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                          />
                        )}
                      </div>

                      {/* Content */}
                      <div className="p-4">
                        <h3 className="font-semibold text-gray-900 mb-2 group-hover:text-primary transition-colors">
                          {listing.title}
                        </h3>

                        <div className="flex items-center justify-between">
                          <div className="flex items-center gap-1">
                            {listing.rating && (
                              <>
                                <svg
                                  className="w-4 h-4 text-secondary fill-secondary"
                                  viewBox="0 0 20 20"
                                >
                                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <span className="text-sm font-medium text-gray-900">
                                  {listing.rating.toFixed(1)}
                                </span>
                                <span className="text-sm text-gray-500">
                                  ({listing.reviewsCount})
                                </span>
                              </>
                            )}
                          </div>
                          <div className="text-right">
                            <div className="text-sm text-gray-500">From</div>
                            <div className="font-bold text-primary">
                              {listing.pricing.displayCurrency || 'EUR'}{' '}
                              {(
                                listing.pricing.displayPrice ||
                                listing.pricing.tndPrice ||
                                0
                              ).toFixed(2)}
                            </div>
                          </div>
                        </div>
                      </div>
                    </Link>
                  ))}
                </div>
              ) : (
                <div className="text-center py-12">
                  <p className="text-gray-600">No listings available yet.</p>
                </div>
              )}
            </div>
          )}

          {activeTab === 'reviews' && (
            <div className="text-center py-12">
              <p className="text-gray-600">Reviews will be displayed here.</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
