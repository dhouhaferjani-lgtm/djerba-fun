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
import { Card } from '@go-adventure/ui';
import { RatingStars } from './RatingStars';
import { PriceDisplay } from './PriceDisplay';
import { Clock, MapPin } from 'lucide-react';
import type { ListingSummary } from '@go-adventure/schemas';
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
  const t = (field: any) => resolveTranslation(field, locale);

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
              alt={t(mainImageAlt) || t(listing.title)}
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

          {/* Activity Type Badge (Tours only) */}
          {listing.serviceType === 'tour' && listing.activityType && (
            <div className="absolute top-3 left-3">
              <span
                className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-white/95 backdrop-blur-sm shadow-sm"
                style={
                  listing.activityType.color
                    ? { color: listing.activityType.color }
                    : { color: '#0D642E' }
                }
              >
                {t(listing.activityType.name)}
              </span>
            </div>
          )}
        </div>

        {/* Content */}
        <div className="p-4 flex flex-col gap-3">
          {/* Title and Rating */}
          <div>
            <h3 className="font-semibold text-lg text-neutral-900 line-clamp-2 mb-1 group-hover:text-primary transition-colors">
              {t(listing.title)}
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
            <span>{t(listing.location?.name) || 'Tunisia'}</span>
          </div>

          {/* Duration (if available) */}
          {listing.duration && (
            <div className="flex items-center gap-1.5 text-sm text-neutral-600">
              <Clock className="h-4 w-4" />
              <span>
                {listing.duration.value} {listing.duration.unit}
              </span>
            </div>
          )}

          {/* Price */}
          <div className="mt-auto pt-3 border-t border-neutral-100">
            {listing.pricing.displayPrice || listing.pricing.tndPrice ? (
              <PriceDisplay
                amount={listing.pricing.displayPrice || listing.pricing.tndPrice || 0}
                currency={listing.pricing.displayCurrency || 'TND'}
                size="sm"
                showFrom
              />
            ) : (
              <span className="text-sm text-neutral-500">Price on request</span>
            )}
          </div>
        </div>
      </Card>
    </Link>
  );
}

// Memoize to prevent unnecessary re-renders
export const ListingCard = memo(ListingCardComponent);
