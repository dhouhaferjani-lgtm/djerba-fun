'use client';

import { useEffect } from 'react';
import { useTranslations } from 'next-intl';
import { Heart, ChevronRight } from 'lucide-react';
import { Link, useRouter } from '@/i18n/navigation';
import { useWishlist, useToggleWishlist, useCurrentUser } from '@/lib/api/hooks';
import { Card } from '@djerba-fun/ui';
import Image from 'next/image';

export default function WishlistPage() {
  const t = useTranslations('wishlist');
  const tDashboard = useTranslations('dashboard');
  const router = useRouter();

  const { data: user, isLoading: isLoadingUser } = useCurrentUser();
  const { data, isLoading, error } = useWishlist();
  const toggleMutation = useToggleWishlist();

  useEffect(() => {
    if (!isLoadingUser && !user) {
      router.push('/auth/login');
    }
  }, [user, isLoadingUser, router]);

  const handleRemove = async (listingId: string) => {
    try {
      await toggleMutation.mutateAsync(listingId);
      // The wishlist will update via React Query's optimistic update
    } catch {
      // Error handled by optimistic update rollback
    }
  };

  if (isLoadingUser || isLoading) {
    return (
      <div className="min-h-screen bg-gray-50 py-12">
        <div className="container mx-auto px-4">
          <div className="max-w-6xl mx-auto">
            <div className="mb-8">
              <div className="h-8 bg-gray-200 rounded w-48 mb-2 animate-pulse" />
              <div className="h-4 bg-gray-200 rounded w-32 animate-pulse" />
            </div>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {[1, 2, 3].map((i) => (
                <Card key={i} className="animate-pulse overflow-hidden">
                  <div className="aspect-video bg-gray-200" />
                  <div className="p-4 space-y-2">
                    <div className="h-4 bg-gray-200 rounded w-3/4" />
                    <div className="h-3 bg-gray-200 rounded w-1/2" />
                  </div>
                </Card>
              ))}
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-gray-50 py-12">
        <div className="container mx-auto px-4">
          <div className="max-w-6xl mx-auto text-center py-12">
            <p className="text-red-500">{t('error_loading')}</p>
          </div>
        </div>
      </div>
    );
  }

  const wishlistItems = data?.data ?? [];

  if (wishlistItems.length === 0) {
    return (
      <div className="min-h-screen bg-gray-50 py-12">
        <div className="container mx-auto px-4">
          <div className="max-w-6xl mx-auto">
            <div className="mb-8">
              <div className="flex items-center justify-between">
                <div>
                  <h1 className="text-3xl font-bold text-gray-900 mb-2">{t('title')}</h1>
                </div>
                <Link href="/dashboard" className="text-primary hover:text-primary/80 font-medium">
                  ← {tDashboard('back_to_dashboard')}
                </Link>
              </div>
            </div>
            <div
              data-testid="wishlist-empty"
              className="bg-white rounded-lg shadow-sm border border-gray-200 p-12 flex flex-col items-center justify-center text-center"
            >
              <Heart className="h-16 w-16 text-gray-300 mb-4" />
              <h2 className="text-xl font-semibold text-gray-700 mb-2">{t('empty_title')}</h2>
              <p className="text-gray-500 mb-6 max-w-md">{t('empty_description')}</p>
              <Link
                href="/listings"
                className="inline-flex items-center px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors"
              >
                {t('browse_listings')}
                <ChevronRight className="ml-2 h-4 w-4" />
              </Link>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 py-12">
      <div className="container mx-auto px-4">
        <div className="max-w-6xl mx-auto">
          {/* Header */}
          <div className="mb-8">
            <div className="flex items-center justify-between">
              <div>
                <h1 className="text-3xl font-bold text-gray-900 mb-2">{t('title')}</h1>
                <span className="text-gray-500">{t('count', { count: wishlistItems.length })}</span>
              </div>
              <Link href="/dashboard" className="text-primary hover:text-primary/80 font-medium">
                ← {tDashboard('back_to_dashboard')}
              </Link>
            </div>
          </div>

          {/* Wishlist Grid */}
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {wishlistItems.map((item) => (
              <Card key={item.id} className="overflow-hidden group" data-testid="wishlist-item">
                <Link href={`/listings/${item.listing?.slug}`}>
                  <div className="relative aspect-video">
                    {item.listing?.hero_image ? (
                      <Image
                        src={item.listing.hero_image}
                        alt={item.listing.title || ''}
                        fill
                        className="object-cover group-hover:scale-105 transition-transform duration-300"
                      />
                    ) : (
                      <div className="w-full h-full bg-gray-200 flex items-center justify-center">
                        <Heart className="h-8 w-8 text-gray-400" />
                      </div>
                    )}
                    <button
                      type="button"
                      onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        handleRemove(item.listing_id);
                      }}
                      data-testid="remove-from-wishlist"
                      className="absolute top-2 right-2 p-2 bg-white/90 rounded-full hover:bg-white transition-colors shadow-sm"
                      aria-label={t('remove_aria')}
                    >
                      <Heart className="h-5 w-5 fill-red-500 stroke-red-500" />
                    </button>
                  </div>
                  <div className="p-4">
                    <h3 className="font-semibold text-gray-900 group-hover:text-emerald-600 transition-colors line-clamp-2">
                      {item.listing?.title}
                    </h3>
                    {item.listing?.location && (
                      <p className="text-sm text-gray-500 mt-1">{item.listing.location.name}</p>
                    )}
                    {item.listing?.pricing && (
                      <p className="text-emerald-600 font-semibold mt-2">
                        {item.listing.pricing.display_currency} {item.listing.pricing.display_price}
                      </p>
                    )}
                    {item.listing?.rating_average !== null &&
                      item.listing?.rating_average !== undefined && (
                        <div className="flex items-center gap-1 mt-2">
                          <span className="text-yellow-500">★</span>
                          <span className="text-sm text-gray-600">
                            {item.listing.rating_average.toFixed(1)}
                          </span>
                          {item.listing.review_count > 0 && (
                            <span className="text-sm text-gray-400">
                              ({item.listing.review_count})
                            </span>
                          )}
                        </div>
                      )}
                  </div>
                </Link>
              </Card>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
