'use client';

/* eslint-disable @typescript-eslint/no-explicit-any -- typed routes for dynamic hrefs */
import { useTranslations, useLocale } from 'next-intl';
import Image from 'next/image';
import { Button } from '@djerba-fun/ui';
import { useRouter } from 'next/navigation';
import { Award, Leaf, Globe } from 'lucide-react';
import { getListingUrl } from '@/lib/utils/urls';
import type { Locale } from '@/i18n/routing';
import type { ListingSummary } from '@djerba-fun/schemas';
import { ListingCard } from '@/components/molecules/ListingCard';

interface FeaturedPackagesSectionProps {
  listings?: ListingSummary[];
  locale?: string;
}

export function FeaturedPackagesSection({
  listings,
  locale: localeProp,
}: FeaturedPackagesSectionProps) {
  const t = useTranslations('home');
  const localeFromHook = useLocale() as Locale;
  const locale = (localeProp || localeFromHook) as Locale;
  const router = useRouter();

  // If we have real listings from the API, use them
  if (listings && listings.length > 0) {
    return (
      <section className="py-20 bg-white">
        <div className="container mx-auto px-4">
          {/* Header with View All Link */}
          <div className="flex justify-between items-center mb-12">
            <h2 className="text-3xl md:text-4xl font-display font-bold text-neutral-900">
              {t('upcoming_adventures')}
            </h2>
            <Button variant="outline" onClick={() => router.push(`/${locale}/listings` as any)}>
              {t('view_all_adventures')}
            </Button>
          </div>

          <div className="grid md:grid-cols-3 gap-8">
            {listings.slice(0, 3).map((listing) => (
              <ListingCard key={listing.id} listing={listing} locale={locale} />
            ))}
          </div>
        </div>
      </section>
    );
  }

  // Fallback to hardcoded packages if no listings from API
  const featuredPackages = [
    {
      icon: <Award className="w-12 h-12" />,
      title: t('featured_package_1_title'),
      description: t('featured_package_1_description'),
      image: 'https://images.unsplash.com/photo-1590059390047-f5e617690a0b?w=800&q=80',
      slug: 'djerba-island-discovery-tour',
      location: 'djerba',
    },
    {
      icon: <Leaf className="w-12 h-12" />,
      title: t('featured_package_2_title'),
      description: t('featured_package_2_description'),
      image: 'https://images.unsplash.com/photo-1509099836639-18ba1795216d?w=800&q=80',
      slug: 'sahara-desert-camel-trek',
      location: 'tozeur',
    },
    {
      icon: <Globe className="w-12 h-12" />,
      title: t('featured_package_3_title'),
      description: t('featured_package_3_description'),
      image: 'https://images.unsplash.com/photo-1682687220742-aba13b6e50ba?w=800&q=80',
      slug: 'kroumirie-mountains-summit-trek',
      location: 'ain-draham',
    },
  ];

  return (
    <section className="py-20 bg-white">
      <div className="container mx-auto px-4">
        {/* Header with View All Link */}
        <div className="flex justify-between items-center mb-12">
          <h2 className="text-3xl md:text-4xl font-display font-bold text-neutral-900">
            {t('upcoming_adventures')}
          </h2>
          <Button variant="outline" onClick={() => router.push(`/${locale}/listings` as any)}>
            {t('view_all_adventures')}
          </Button>
        </div>

        <div className="grid md:grid-cols-3 gap-8">
          {featuredPackages.map((pkg, index) => (
            <div
              key={index}
              className="green-click-shadow bg-white rounded-lg shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col group cursor-pointer"
              onClick={() => router.push(getListingUrl(pkg.slug, pkg.location, locale) as any)}
            >
              {/* Image with Badge */}
              <div className="relative h-64 w-full overflow-hidden">
                <Image
                  src={pkg.image}
                  alt={pkg.title}
                  fill
                  sizes="(max-width: 768px) 100vw, (max-width: 1200px) 33vw, 400px"
                  className="object-cover transition-transform duration-300 group-hover:scale-110"
                />
                {/* Badge (Category) on Top-Left */}
                <div className="absolute top-4 left-4">
                  <div className="bg-primary text-white px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide">
                    {index === 0
                      ? t('badge_tour')
                      : index === 1
                        ? t('badge_event')
                        : t('badge_adventure')}
                  </div>
                </div>
              </div>

              {/* Card Body */}
              <div className="p-6 flex flex-col flex-grow">
                <h3 className="text-xl font-bold text-neutral-900 mb-3 line-clamp-2 group-hover:text-primary transition-colors">
                  {pkg.title}
                </h3>
                <p className="text-neutral-600 text-sm leading-relaxed mb-4 flex-grow line-clamp-2">
                  {pkg.description}
                </p>

                {/* View Details Link */}
                <div className="text-primary font-semibold text-sm group-hover:underline">
                  {t('view_details')}
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
