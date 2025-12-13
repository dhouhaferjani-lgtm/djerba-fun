/* eslint-disable @typescript-eslint/no-explicit-any -- next-intl Link requires typed routes, using any for dynamic hrefs */
import Link from 'next/link';
import Image from 'next/image';
import { Card } from '@go-adventure/ui';
import { RatingStars } from './RatingStars';
import { PriceDisplay } from './PriceDisplay';
import { Clock, MapPin } from 'lucide-react';
import type { ListingSummary } from '@go-adventure/schemas';

interface ListingCardProps {
  listing: ListingSummary;
  locale: string;
}

export function ListingCard({ listing, locale }: ListingCardProps) {
  const mainImage = listing.media[0];
  const href = `/${locale}/listings/${listing.slug}` as any;

  return (
    <Link href={href}>
      <Card variant="interactive" padding="none" className="overflow-hidden h-full">
        {/* Image */}
        <div className="relative h-48 w-full bg-neutral-100">
          {mainImage ? (
            <Image
              src={mainImage.url}
              alt={mainImage.alt}
              fill
              className="object-cover"
              sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
            />
          ) : (
            <div className="flex items-center justify-center h-full text-neutral-400">
              <MapPin className="h-12 w-12" />
            </div>
          )}
        </div>

        {/* Content */}
        <div className="p-4 flex flex-col gap-3">
          {/* Title and Rating */}
          <div>
            <h3 className="font-semibold text-lg text-neutral-900 line-clamp-2 mb-1">
              {listing.title}
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
            <span>{listing.location.name}</span>
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
            <PriceDisplay
              amount={listing.pricing.from}
              currency={listing.pricing.currency}
              size="sm"
              showFrom
            />
          </div>
        </div>
      </Card>
    </Link>
  );
}
