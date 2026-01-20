'use client';

import { useRouter, useSearchParams } from 'next/navigation';
import { ChevronDown, X } from 'lucide-react';

interface BlogFiltersProps {
  locale: string;
  tags: string[];
  translations: {
    filterByDate: string;
    filterByTag: string;
    allTags: string;
    newestFirst: string;
    oldestFirst: string;
    clearFilters: string;
  };
}

export function BlogFilters({ locale, tags, translations }: BlogFiltersProps) {
  const router = useRouter();
  const searchParams = useSearchParams();

  const currentSort = searchParams.get('sort') || 'newest';
  const currentTag = searchParams.get('tag') || '';

  const hasActiveFilters = currentSort !== 'newest' || currentTag !== '';

  const handleSortChange = (value: string) => {
    const params = new URLSearchParams(searchParams.toString());
    if (value === 'newest') {
      params.delete('sort');
    } else {
      params.set('sort', value);
    }
    router.push(`/${locale}/blog?${params.toString()}`);
  };

  const handleTagChange = (value: string) => {
    const params = new URLSearchParams(searchParams.toString());
    if (value === '') {
      params.delete('tag');
    } else {
      params.set('tag', value);
    }
    router.push(`/${locale}/blog?${params.toString()}`);
  };

  const clearFilters = () => {
    router.push(`/${locale}/blog`);
  };

  return (
    <div className="flex flex-wrap items-center gap-4 mb-8">
      {/* Sort by Date */}
      <div className="relative">
        <label className="block text-sm font-medium text-gray-700 mb-1">
          {translations.filterByDate}
        </label>
        <div className="relative">
          <select
            value={currentSort}
            onChange={(e) => handleSortChange(e.target.value)}
            className="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent cursor-pointer"
          >
            <option value="newest">{translations.newestFirst}</option>
            <option value="oldest">{translations.oldestFirst}</option>
          </select>
          <ChevronDown className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" />
        </div>
      </div>

      {/* Filter by Tag */}
      {tags.length > 0 && (
        <div className="relative">
          <label className="block text-sm font-medium text-gray-700 mb-1">
            {translations.filterByTag}
          </label>
          <div className="relative">
            <select
              value={currentTag}
              onChange={(e) => handleTagChange(e.target.value)}
              className="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent cursor-pointer"
            >
              <option value="">{translations.allTags}</option>
              {tags.map((tag) => (
                <option key={tag} value={tag}>
                  {tag}
                </option>
              ))}
            </select>
            <ChevronDown className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" />
          </div>
        </div>
      )}

      {/* Clear Filters */}
      {hasActiveFilters && (
        <button
          onClick={clearFilters}
          className="flex items-center gap-1 text-sm text-primary hover:text-primary/80 transition-colors mt-6"
        >
          <X className="w-4 h-4" />
          {translations.clearFilters}
        </button>
      )}
    </div>
  );
}
