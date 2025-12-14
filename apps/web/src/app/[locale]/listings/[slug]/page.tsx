'use client';

import Image from 'next/image';
import { useParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { MainLayout } from '@/components/templates/MainLayout';
import { useListing } from '@/lib/api/hooks';
import { Button, Card } from '@go-adventure/ui';
import { RatingStars } from '@/components/molecules/RatingStars';
import { PriceDisplay } from '@/components/molecules/PriceDisplay';
import { MapPin, Clock, Users, Calendar, CheckCircle, XCircle, AlertCircle } from 'lucide-react';
import { resolveTranslation } from '@/lib/utils/translate';

export default function ListingDetailPage() {
  const params = useParams();
  const locale = params.locale as string;
  const slug = params.slug as string;
  const t = useTranslations('listing');
  const tCommon = useTranslations('common');

  const { data: listing, isLoading, error } = useListing(slug);

  if (isLoading) {
    return (
      <MainLayout locale={locale}>
        <div className="container mx-auto px-4 py-12">
          <p className="text-center text-lg text-neutral-500">{tCommon('loading')}</p>
        </div>
      </MainLayout>
    );
  }

  if (error || !listing) {
    return (
      <MainLayout locale={locale}>
        <div className="container mx-auto px-4 py-12">
          <p className="text-center text-lg text-red-500">{tCommon('error')}</p>
        </div>
      </MainLayout>
    );
  }

  const mainImage = listing.media[0];
  const tr = (field: any) => resolveTranslation(field, locale);
  const title = tr(listing.title);
  const description = tr(listing.description);

  return (
    <MainLayout locale={locale}>
      {/* Hero Image */}
      <div className="relative h-96 w-full bg-neutral-100">
        {mainImage && (
          <Image
            src={mainImage.url}
            alt={tr(mainImage.alt) || title}
            fill
            className="object-cover"
            priority
          />
        )}
      </div>

      <div className="container mx-auto px-4 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main Content */}
          <div className="lg:col-span-2 space-y-8">
            {/* Title and Rating */}
            <div>
              <h1 className="text-4xl font-bold text-neutral-900 mb-4">{title}</h1>
              {listing.rating && (
                <div className="flex items-center gap-2">
                  <RatingStars rating={listing.rating} showNumber />
                  <span className="text-sm text-neutral-500">
                    ({tCommon('reviews', { count: listing.reviewsCount || 0 })})
                  </span>
                </div>
              )}
            </div>

            {/* Quick Info */}
            <div className="flex flex-wrap gap-6 text-sm">
              {listing.serviceType === 'tour' && listing.duration && (
                <div className="flex items-center gap-2 text-neutral-600">
                  <Clock className="h-5 w-5" />
                  <span>
                    {listing.duration.value} {listing.duration.unit}
                  </span>
                </div>
              )}
              <div className="flex items-center gap-2 text-neutral-600">
                <Users className="h-5 w-5" />
                <span>Max {listing.maxGroupSize} guests</span>
              </div>
              <div className="flex items-center gap-2 text-neutral-600">
                <MapPin className="h-5 w-5" />
                <span>{listing.meetingPoint.address}</span>
              </div>
            </div>

            {/* Description */}
            <div>
              <h2 className="text-2xl font-semibold text-neutral-900 mb-4">About</h2>
              <p className="text-neutral-700 whitespace-pre-line">{description}</p>
            </div>

            {/* Highlights */}
            {listing.highlights.length > 0 && (
              <div>
                <h2 className="text-2xl font-semibold text-neutral-900 mb-4">{t('highlights')}</h2>
                <ul className="space-y-2">
                  {listing.highlights.map((highlight, index) => (
                    <li key={index} className="flex items-start gap-2">
                      <CheckCircle className="h-5 w-5 text-[#8BC34A] flex-shrink-0 mt-0.5" />
                      <span className="text-neutral-700">{tr(highlight)}</span>
                    </li>
                  ))}
                </ul>
              </div>
            )}

            {/* Included / Not Included */}
            <div className="grid md:grid-cols-2 gap-6">
              {listing.included.length > 0 && (
                <div>
                  <h3 className="font-semibold text-neutral-900 mb-3">{t('included')}</h3>
                  <ul className="space-y-2">
                    {listing.included.map((item, index) => (
                      <li key={index} className="flex items-start gap-2 text-sm">
                        <CheckCircle className="h-4 w-4 text-green-500 flex-shrink-0 mt-0.5" />
                        <span className="text-neutral-600">{tr(item)}</span>
                      </li>
                    ))}
                  </ul>
                </div>
              )}

              {listing.notIncluded.length > 0 && (
                <div>
                  <h3 className="font-semibold text-neutral-900 mb-3">{t('not_included')}</h3>
                  <ul className="space-y-2">
                    {listing.notIncluded.map((item, index) => (
                      <li key={index} className="flex items-start gap-2 text-sm">
                        <XCircle className="h-4 w-4 text-red-500 flex-shrink-0 mt-0.5" />
                        <span className="text-neutral-600">{tr(item)}</span>
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </div>

            {/* Requirements */}
            {listing.requirements.length > 0 && (
              <div>
                <h3 className="font-semibold text-neutral-900 mb-3">Requirements</h3>
                <ul className="space-y-2">
                  {listing.requirements.map((req, index) => (
                    <li key={index} className="flex items-start gap-2 text-sm">
                      <AlertCircle className="h-4 w-4 text-[#f59e0b] flex-shrink-0 mt-0.5" />
                      <span className="text-neutral-600">{tr(req)}</span>
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </div>

          {/* Sidebar - Booking Card */}
          <div className="lg:col-span-1">
            <Card className="sticky top-20">
              <div className="p-6 space-y-6">
                <PriceDisplay
                  amount={listing.pricing.basePrice}
                  currency={listing.pricing.currency}
                  size="lg"
                  showFrom
                />

                <div className="space-y-3">
                  <Button variant="primary" size="lg" className="w-full">
                    <Calendar className="h-5 w-5 mr-2" />
                    {t('check_availability')}
                  </Button>
                  <p className="text-xs text-neutral-500 text-center">
                    Free cancellation up to 24 hours before
                  </p>
                </div>

                <div className="pt-6 border-t border-neutral-200">
                  <div className="space-y-3 text-sm">
                    <div className="flex items-center gap-2 text-neutral-600">
                      <CheckCircle className="h-4 w-4 text-[#8BC34A]" />
                      <span>Instant confirmation</span>
                    </div>
                    <div className="flex items-center gap-2 text-neutral-600">
                      <CheckCircle className="h-4 w-4 text-[#8BC34A]" />
                      <span>Mobile ticket accepted</span>
                    </div>
                  </div>
                </div>
              </div>
            </Card>
          </div>
        </div>
      </div>
    </MainLayout>
  );
}
