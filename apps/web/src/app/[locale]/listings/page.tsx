'use client';

import { Suspense, useState } from 'react';
import { useSearchParams, useParams, useRouter } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { MainLayout } from '@/components/templates/MainLayout';
import { ListingGrid } from '@/components/organisms/ListingGrid';
import { SearchBar } from '@/components/molecules/SearchBar';
import { TagFilterGroup } from '@/components/molecules/TagFilterGroup';
import { useListings, useLocations, useTagsForServiceType } from '@/lib/api/hooks';
import { Button } from '@djerba-fun/ui';
import { Filter, X, SlidersHorizontal } from 'lucide-react';

function ListingsContent({ locale }: { locale: string }) {
  const searchParams = useSearchParams();
  const router = useRouter();
  const t = useTranslations('common');
  const tListings = useTranslations('listings_page');
  const [showFilters, setShowFilters] = useState(false);

  // Get sort from URL or default to newest for better discovery
  const currentSort = (searchParams.get('sort') || 'newest') as
    | 'newest'
    | 'popularity'
    | 'price_asc'
    | 'price_desc'
    | 'rating';

  const serviceType = searchParams.get('type') as
    | 'tour'
    | 'nautical'
    | 'accommodation'
    | 'event'
    | undefined;
  const selectedTags = searchParams.get('tags')?.split(',').filter(Boolean) || [];

  const queryParams = {
    serviceType,
    location: searchParams.get('location') || undefined,
    search: searchParams.get('q') || undefined,
    activityType: searchParams.get('activity_type') || undefined,
    tags: selectedTags.length > 0 ? selectedTags.join(',') : undefined,
    sort: currentSort,
    limit: 20,
  };

  const { data, isLoading, error } = useListings(queryParams, locale);
  const { data: locations } = useLocations();
  const { data: tagGroups } = useTagsForServiceType(serviceType || '', !!serviceType);

  const handleTagToggle = (tagSlug: string) => {
    const params = new URLSearchParams(searchParams.toString());
    const currentTags = params.get('tags')?.split(',').filter(Boolean) || [];

    let newTags: string[];
    if (currentTags.includes(tagSlug)) {
      newTags = currentTags.filter((t) => t !== tagSlug);
    } else {
      newTags = [...currentTags, tagSlug];
    }

    if (newTags.length > 0) {
      params.set('tags', newTags.join(','));
    } else {
      params.delete('tags');
    }
    router.push(`/${locale}/listings?${params.toString()}`);
  };

  const handleSearch = (query: string) => {
    const params = new URLSearchParams(searchParams.toString());
    if (query) {
      params.set('q', query);
    } else {
      params.delete('q');
    }
    router.push(`/${locale}/listings?${params.toString()}`);
  };

  const handleFilterChange = (key: string, value: string | null) => {
    const params = new URLSearchParams(searchParams.toString());
    if (value) {
      params.set(key, value);
    } else {
      params.delete(key);
    }
    router.push(`/${locale}/listings?${params.toString()}`);
  };

  const clearFilters = () => {
    router.push(`/${locale}/listings`);
  };

  const hasActiveFilters =
    searchParams.get('type') ||
    searchParams.get('location') ||
    searchParams.get('q') ||
    searchParams.get('activity_type') ||
    searchParams.get('tags');

  return (
    <MainLayout locale={locale}>
      <div className="bg-neutral-50 border-b border-neutral-200">
        <div className="container mx-auto px-4 py-8">
          <h1 className="text-3xl font-bold text-neutral-900 mb-6">
            {queryParams.serviceType === 'tour'
              ? tListings('title_tours')
              : queryParams.serviceType === 'nautical'
                ? tListings('title_nautical')
                : queryParams.serviceType === 'accommodation'
                  ? tListings('title_accommodations')
                  : queryParams.serviceType === 'event'
                    ? tListings('title_events')
                    : tListings('title_all')}
          </h1>
          <div className="flex flex-col md:flex-row gap-4">
            <div className="flex-1">
              <SearchBar
                placeholder={tListings('search_placeholder')}
                onSearch={handleSearch}
                defaultValue={searchParams.get('q') || ''}
              />
            </div>
            <Button variant="outline" onClick={() => setShowFilters(!showFilters)}>
              <Filter className="h-4 w-4 mr-2" />
              {tListings('filters')}
              {hasActiveFilters && (
                <span className="ml-2 bg-primary text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">
                  !
                </span>
              )}
            </Button>
          </div>

          {/* Filters Panel */}
          {showFilters && (
            <div className="mt-6 p-6 bg-white rounded-lg border border-neutral-200 shadow-sm">
              <div className="flex items-center justify-between mb-4">
                <h3 className="font-semibold text-neutral-900">{tListings('filters')}</h3>
                <button
                  onClick={() => setShowFilters(false)}
                  className="text-neutral-500 hover:text-neutral-700"
                >
                  <X className="h-5 w-5" />
                </button>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {/* Type Filter */}
                <div>
                  <label className="block text-sm font-medium text-neutral-700 mb-2">
                    {tListings('experience_type')}
                  </label>
                  <select
                    value={searchParams.get('type') || ''}
                    onChange={(e) => handleFilterChange('type', e.target.value || null)}
                    className="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                  >
                    <option value="">{tListings('all_types')}</option>
                    <option value="tour">{tListings('title_tours')}</option>
                    <option value="nautical">{tListings('title_nautical')}</option>
                    <option value="accommodation">{tListings('title_accommodations')}</option>
                    <option value="event">{tListings('title_events')}</option>
                  </select>
                </div>

                {/* Location Filter */}
                <div>
                  <label className="block text-sm font-medium text-neutral-700 mb-2">
                    {tListings('location')}
                  </label>
                  <select
                    value={searchParams.get('location') || ''}
                    onChange={(e) => handleFilterChange('location', e.target.value || null)}
                    className="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                  >
                    <option value="">{tListings('all_locations')}</option>
                    {locations?.map((location) => (
                      <option key={location.id} value={location.slug}>
                        {location.name}
                        {location.city && location.city !== location.name
                          ? ` (${location.city})`
                          : ''}
                      </option>
                    ))}
                  </select>
                </div>

                {/* Sort */}
                <div>
                  <label className="block text-sm font-medium text-neutral-700 mb-2">
                    {tListings('sort_by')}
                  </label>
                  <select
                    value={currentSort}
                    onChange={(e) => handleFilterChange('sort', e.target.value)}
                    className="w-full px-3 py-2 border border-neutral-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                  >
                    <option value="newest">{tListings('sort_newest')}</option>
                    <option value="popularity">{tListings('sort_popular')}</option>
                    <option value="rating">{tListings('sort_highest_rated')}</option>
                    <option value="price_asc">{tListings('sort_price_low')}</option>
                    <option value="price_desc">{tListings('sort_price_high')}</option>
                  </select>
                </div>
              </div>

              {/* Tag Filters - shown when a service type is selected */}
              {serviceType && tagGroups && tagGroups.length > 0 && (
                <div className="mt-4 pt-4 border-t border-neutral-200">
                  <TagFilterGroup
                    tagGroups={tagGroups}
                    selectedTags={selectedTags}
                    onTagToggle={handleTagToggle}
                    locale={locale}
                  />
                </div>
              )}

              {hasActiveFilters && (
                <div className="mt-4 pt-4 border-t border-neutral-200">
                  <Button variant="outline" size="sm" onClick={clearFilters}>
                    {tListings('clear_all_filters')}
                  </Button>
                </div>
              )}
            </div>
          )}
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
            <p className="text-lg text-error">{t('error')}</p>
            <Button variant="outline" className="mt-4">
              {t('retry')}
            </Button>
          </div>
        )}

        {data && (
          <>
            <div className="mb-6 flex items-center justify-between">
              <p className="text-sm text-neutral-600">
                {tListings('found_experiences', { count: data.meta.total })}
              </p>
              <button
                onClick={() => setShowFilters(!showFilters)}
                className="md:hidden flex items-center gap-2 text-sm text-neutral-600 hover:text-neutral-900"
              >
                <SlidersHorizontal className="h-4 w-4" />
                {tListings('sort_and_filter')}
              </button>
            </div>
            <ListingGrid
              listings={data.data}
              locale={locale}
              emptyMessage={tListings('no_results')}
            />
          </>
        )}
      </div>
    </MainLayout>
  );
}

export default function ListingsPage() {
  const params = useParams();
  const locale = params.locale as string;

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
