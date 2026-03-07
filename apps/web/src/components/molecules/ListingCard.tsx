'use client';

/* eslint-disable @typescript-eslint/no-explicit-any -- next-intl Link requires typed routes, using any for dynamic hrefs */
/**
 * Performance Optimization: React.memo applied
 *
 * ListingCard is frequently rendered in grids/lists and benefits from memoization.
 * This prevents unnecessary re-renders when parent components update but props stay the same.
 *
 * Benefits:
 * - Reduces render cycles in listing grids
 * - Improves scrolling performance
 * - Better response when filtering/sorting listings
 */
import { memo } from 'react';
import Link from 'next/link';
import Image from 'next/image';
import { useTranslations } from 'next-intl';
import { Card } from '@djerba-fun/ui';
import { RatingStars } from './RatingStars';
import { PriceDisplay } from './PriceDisplay';
import { WishlistButton } from '@/components/listing/WishlistButton';
import { Calendar, Clock, MapPin } from 'lucide-react';
import type { ListingSummary } from '@djerba-fun/schemas';
import { resolveTranslation } from '@/lib/utils/translate';
import { getListingUrl } from '@/lib/utils/urls';
import { shouldUnoptimizeImage, normalizeMediaUrl } from '@/lib/utils/image';
import type { Locale } from '@/i18n/routing';

interface ListingCardProps {
  listing: ListingSummary;
  locale: string;
}

function ListingCardComponent({ listing, locale }: ListingCardProps) {
  // Prefer galleryImages (from vendor upload), fall back to media
  const galleryImage = listing.galleryImages?.[0];
  const mediaImage = listing.media?.[0];
  const mainImageUrl = galleryImage || mediaImage?.url;
  const mainImageAlt = mediaImage?.alt;
  const href = getListingUrl(listing.slug, listing.location, locale as Locale) as any;
  const tr = (field: any) => resolveTranslation(field, locale);
  const tDict = useTranslations('listing');

  return (
    <Link href={href} className="group">
      <Card
        variant="interactive"
        padding="none"
        className="overflow-hidden h-full green-click-shadow hover:shadow-xl hover:-translate-y-1"
      >
        {/* Image */}
        <div className="relative h-48 w-full bg-neutral-100">
          {mainImageUrl ? (
            <Image
              src={normalizeMediaUrl(mainImageUrl)}
              alt={tr(mainImageAlt) || tr(listing.title)}
              fill
              className="object-cover transition-transform duration-300 group-hover:scale-110"
              sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
              loading="lazy"
              unoptimized={shouldUnoptimizeImage(normalizeMediaUrl(mainImageUrl))}
            />
          ) : (
            <div className="flex items-center justify-center h-full text-neutral-400">
              <MapPin className="h-12 w-12" />
            </div>
          )}

          {/* Activity Type Badge (Tour-like services) */}
          {(listing.serviceType === 'tour' ||
            listing.serviceType === 'nautical' ||
            listing.serviceType === 'accommodation') &&
            listing.activityType && (
              <div className="absolute top-3 left-3">
                <span
                  className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-white/95 backdrop-blur-sm shadow-sm"
                  style={
                    listing.activityType.color
                      ? { color: listing.activityType.color }
                      : { color: '#1B2A4E' }
                  }
                >
                  {tr(listing.activityType.name)}
                </span>
              </div>
            )}

          {/* Multi-day badge (Accommodations) */}
          {listing.serviceType === 'accommodation' && listing.duration?.value && (
            <div className="absolute top-3 right-3">
              <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 shadow-sm">
                <Calendar className="h-3 w-3" />
                {listing.duration.value} {tDict('days')}
              </span>
            </div>
          )}

          {/* Wishlist Button */}
          <WishlistButton
            listingId={listing.id}
            className="absolute bottom-3 right-3 z-10"
            variant="overlay"
            size="sm"
          />
        </div>

        {/* Content */}
        <div className="p-4 flex flex-col gap-3">
          {/* Title and Rating */}
          <div>
            <h3 className="font-semibold text-lg text-neutral-900 line-clamp-2 mb-1 group-hover:text-primary transition-colors">
              {tr(listing.title)}
            </h3>
            {listing.rating && (
              <div className="flex items-center gap-1">
                <RatingStars rating={listing.rating} size="sm" showNumber />
                <span className="text-sm text-neutral-500">({listing.reviewsCount})</span>
              </div>
            )}
          </div>

          {/* Location */}
          <div className="flex items-center gap-1.5 text-sm text-neutral-600">
            <MapPin className="h-4 w-4" />
            <span>{tr(listing.location?.name) || tDict('default_location')}</span>
          </div>

          {/* Duration (if available) */}
          {listing.duration && (
            <div className="flex items-center gap-1.5 text-sm text-neutral-600">
              <Clock className="h-4 w-4" />
              <span>
                {listing.duration.value}{' '}
                {tDict(
                  `duration_unit.${listing.duration.unit || (listing.serviceType === 'accommodation' ? 'days' : 'hours')}`
                )}
              </span>
            </div>
          )}

          {/* Price */}
          <div className="mt-auto pt-3 border-t border-neutral-100">
            {listing.pricing.displayPrice || listing.pricing.tndPrice ? (
              <PriceDisplay
                amount={listing.pricing.displayPrice || listing.pricing.tndPrice || 0}
                currency={listing.pricing.displayCurrency || 'EUR'}
                size="sm"
                showFrom
              />
            ) : (
              <span className="text-sm text-neutral-500">{tDict('price_on_request')}</span>
            )}
          </div>
        </div>
      </Card>
    </Link>
  );
}

// Memoize to prevent unnecessary re-renders
export const ListingCard = memo(ListingCardComponent);
