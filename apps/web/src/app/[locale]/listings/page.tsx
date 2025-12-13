'use client';

import { Suspense, use } from 'react';
import { useSearchParams } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { MainLayout } from '@/components/templates/MainLayout';
import { ListingGrid } from '@/components/organisms/ListingGrid';
import { SearchBar } from '@/components/molecules/SearchBar';
import { useListings } from '@/lib/api/hooks';
import { Button } from '@go-adventure/ui';
import { Filter } from 'lucide-react';

function ListingsContent({ locale }: { locale: string }) {
  const searchParams = useSearchParams();
  const t = useTranslations('common');

  const queryParams = {
    serviceType: searchParams.get('type') as 'tour' | 'event' | undefined,
    location: searchParams.get('location') || undefined,
    sort: 'popularity' as const,
    limit: 20,
  };

  const { data, isLoading, error } = useListings(queryParams);

  return (
    <MainLayout locale={locale}>
      <div className="bg-neutral-50 border-b border-neutral-200">
        <div className="container mx-auto px-4 py-8">
          <h1 className="text-3xl font-bold text-neutral-900 mb-6">
            {queryParams.serviceType === 'tour'
              ? 'Tours & Activities'
              : queryParams.serviceType === 'event'
                ? 'Events'
                : 'All Experiences'}
          </h1>
          <div className="flex flex-col md:flex-row gap-4">
            <div className="flex-1">
              <SearchBar placeholder="Search destinations..." />
            </div>
            <Button variant="outline">
              <Filter className="h-4 w-4 mr-2" />
              Filters
            </Button>
          </div>
        </div>
      </div>

      <div className="container mx-auto px-4 py-12">
        {isLoading && (
          <div className="text-center py-12">
            <p className="text-lg text-neutral-500">{t('loading')}</p>
          </div>
        )}

        {error && (
          <div className="text-center py-12">
            <p className="text-lg text-red-500">{t('error')}</p>
            <Button variant="outline" className="mt-4">
              {t('retry')}
            </Button>
          </div>
        )}

        {data && (
          <>
            <div className="mb-6">
              <p className="text-sm text-neutral-600">Found {data.meta.total} experiences</p>
            </div>
            <ListingGrid
              listings={data.data}
              locale={locale}
              emptyMessage="No listings found. Try adjusting your search."
            />
          </>
        )}
      </div>
    </MainLayout>
  );
}

export default function ListingsPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = use(params);

  return (
    <Suspense
      fallback={
        <MainLayout locale={locale}>
          <div className="container mx-auto px-4 py-12">
            <p className="text-center text-lg text-neutral-500">Loading...</p>
          </div>
        </MainLayout>
      }
    >
      <ListingsContent locale={locale} />
    </Suspense>
  );
}
