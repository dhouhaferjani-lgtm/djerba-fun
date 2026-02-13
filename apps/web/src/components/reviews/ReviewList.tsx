'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import type { Review } from '@go-adventure/schemas';
import ReviewCard from './ReviewCard';

interface ReviewListProps {
  reviews: Review[];
  totalCount: number;
  currentPage?: number;
  onPageChange?: (page: number) => void;
  onSortChange?: (sort: string) => void;
  onFilterChange?: (rating: number | null) => void;
  isLoading?: boolean;
}

export default function ReviewList({
  reviews,
  totalCount,
  currentPage = 1,
  onPageChange,
  onSortChange,
  onFilterChange,
  isLoading,
}: ReviewListProps) {
  const t = useTranslations('reviews');
  const [selectedSort, setSelectedSort] = useState('newest');
  const [selectedRating, setSelectedRating] = useState<number | null>(null);

  const handleSortChange = (sort: string) => {
    setSelectedSort(sort);
    onSortChange?.(sort);
  };

  const handleFilterChange = (rating: number | null) => {
    setSelectedRating(rating);
    onFilterChange?.(rating);
  };

  if (isLoading) {
    return (
      <div className="space-y-4">
        {[1, 2, 3].map((i) => (
          <div key={i} className="border border-gray-200 rounded-lg p-6 bg-white animate-pulse">
            <div className="flex items-start gap-3 mb-4">
              <div className="w-12 h-12 rounded-full bg-gray-200" />
              <div className="flex-1">
                <div className="h-4 bg-gray-200 rounded w-32 mb-2" />
                <div className="h-3 bg-gray-200 rounded w-48" />
              </div>
            </div>
            <div className="space-y-2">
              <div className="h-4 bg-gray-200 rounded w-full" />
              <div className="h-4 bg-gray-200 rounded w-3/4" />
            </div>
          </div>
        ))}
      </div>
    );
  }

  if (reviews.length === 0) {
    return (
      <div className="text-center py-12">
        <div className="text-gray-400 mb-3">
          <svg className="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
            />
          </svg>
        </div>
        <h3 className="text-lg font-semibold text-gray-900 mb-1">{t('no_reviews')}</h3>
        <p className="text-gray-600">{t('be_first')}</p>
      </div>
    );
  }

  return (
    <div>
      {/* Filters and Sort */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        {/* Rating Filter */}
        <div className="flex items-center gap-2 flex-wrap">
          <button
            onClick={() => handleFilterChange(null)}
            className={`px-3 py-1.5 rounded-full text-sm font-medium transition-colors ${
              selectedRating === null
                ? 'bg-primary text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            All
          </button>
          {[5, 4, 3, 2, 1].map((rating) => (
            <button
              key={rating}
              onClick={() => handleFilterChange(rating)}
              className={`px-3 py-1.5 rounded-full text-sm font-medium transition-colors flex items-center gap-1 ${
                selectedRating === rating
                  ? 'bg-primary text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
            >
              {rating}
              <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            </button>
          ))}
        </div>

        {/* Sort Dropdown */}
        <div className="flex items-center gap-2">
          <label htmlFor="sort" className="text-sm text-gray-700 font-medium">
            Sort by:
          </label>
          <select
            id="sort"
            value={selectedSort}
            onChange={(e) => handleSortChange(e.target.value)}
            className="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-transparent"
          >
            <option value="newest">{t('sort_newest')}</option>
            <option value="highest">{t('sort_highest')}</option>
          </select>
        </div>
      </div>

      {/* Reviews */}
      <div className="space-y-4">
        {reviews.map((review) => (
          <ReviewCard key={review.id} review={review} />
        ))}
      </div>

      {/* Pagination */}
      {totalCount > reviews.length && onPageChange && (
        <div className="mt-8 flex justify-center">
          <button
            onClick={() => onPageChange(currentPage + 1)}
            className="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors"
          >
            Load More Reviews
          </button>
        </div>
      )}
    </div>
  );
}
