'use client';

/**
 * Performance Optimization: React.memo and useCallback applied
 *
 * ReviewCard appears in lists and benefits from memoization to prevent
 * unnecessary re-renders when parent state changes.
 *
 * Benefits:
 * - Reduces render cycles in review lists
 * - Better performance on pages with many reviews
 * - Optimized image loading with error handling
 */

import { useState, memo, useCallback } from 'react';
import { useTranslations } from 'next-intl';
import type { Review } from '@go-adventure/schemas';
import { formatDistanceToNow } from 'date-fns';
import { enUS, fr } from 'date-fns/locale';
import { useLocale } from 'next-intl';

interface ReviewCardProps {
  review: Review;
  onMarkHelpful?: (reviewId: string) => void;
  isMarkingHelpful?: boolean;
}

// Memoized StarRating component
const StarRating = memo(({ rating }: { rating: number }) => {
  return (
    <div className="flex gap-0.5">
      {[1, 2, 3, 4, 5].map((star) => (
        <svg
          key={star}
          className={`w-5 h-5 ${star <= rating ? 'text-warning fill-warning' : 'text-gray-300'}`}
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="currentColor"
        >
          <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
        </svg>
      ))}
    </div>
  );
});

StarRating.displayName = 'StarRating';

function ReviewCardComponent({ review, onMarkHelpful, isMarkingHelpful }: ReviewCardProps) {
  const t = useTranslations('reviews');
  const locale = useLocale();
  const [imageError, setImageError] = useState(false);

  const dateLocale = locale === 'fr' ? fr : enUS;
  const timeAgo = formatDistanceToNow(new Date(review.createdAt), {
    addSuffix: true,
    locale: dateLocale,
  });

  const avatarFallback = review.user?.displayName?.charAt(0).toUpperCase() || '?';

  // Memoize the image error handler
  const handleImageError = useCallback(() => {
    setImageError(true);
  }, []);

  // Memoize the helpful button click handler
  const handleMarkHelpful = useCallback(() => {
    if (onMarkHelpful) {
      onMarkHelpful(review.id);
    }
  }, [onMarkHelpful, review.id]);

  return (
    <div className="border border-gray-200 rounded-lg p-6 bg-white">
      {/* Header */}
      <div className="flex items-start justify-between mb-4">
        <div className="flex items-center gap-3">
          {/* Avatar */}
          <div className="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center font-semibold text-lg overflow-hidden">
            {review.user?.avatarUrl && !imageError ? (
              <img
                src={review.user.avatarUrl}
                alt={review.user.displayName}
                className="w-full h-full object-cover"
                onError={handleImageError}
                loading="lazy"
              />
            ) : (
              avatarFallback
            )}
          </div>

          {/* User info */}
          <div>
            <h4 className="font-semibold text-gray-900">
              {review.user?.displayName || 'Anonymous'}
            </h4>
            <div className="flex items-center gap-2 text-sm text-gray-500">
              <span>{timeAgo}</span>
              {review.isVerified && (
                <>
                  <span>•</span>
                  <span className="flex items-center gap-1 text-success">
                    <svg
                      className="w-4 h-4"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        fillRule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clipRule="evenodd"
                      />
                    </svg>
                    {t('verified_booking')}
                  </span>
                </>
              )}
            </div>
          </div>
        </div>

        <StarRating rating={review.rating} />
      </div>

      {/* Title */}
      {review.title && <h3 className="font-semibold text-gray-900 mb-2">{review.title}</h3>}

      {/* Content */}
      <p className="text-gray-700 mb-4 whitespace-pre-wrap">{review.content}</p>

      {/* Pros and Cons */}
      {(review.pros.length > 0 || review.cons.length > 0) && (
        <div className="grid md:grid-cols-2 gap-4 mb-4">
          {review.pros.length > 0 && (
            <div>
              <h5 className="text-sm font-semibold text-success-dark mb-2 flex items-center gap-1">
                <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    fillRule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clipRule="evenodd"
                  />
                </svg>
                {t('pros')}
              </h5>
              <ul className="space-y-1">
                {review.pros.map((pro, index) => (
                  <li key={index} className="text-sm text-gray-600">
                    • {pro}
                  </li>
                ))}
              </ul>
            </div>
          )}

          {review.cons.length > 0 && (
            <div>
              <h5 className="text-sm font-semibold text-error-dark mb-2 flex items-center gap-1">
                <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    fillRule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clipRule="evenodd"
                  />
                </svg>
                {t('cons')}
              </h5>
              <ul className="space-y-1">
                {review.cons.map((con, index) => (
                  <li key={index} className="text-sm text-gray-600">
                    • {con}
                  </li>
                ))}
              </ul>
            </div>
          )}
        </div>
      )}

      {/* Photos */}
      {review.photos && review.photos.length > 0 && (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-2 mb-4">
          {review.photos.map((photo, index) => (
            <img
              key={index}
              src={photo.url}
              alt={photo.caption || `Review photo ${index + 1}`}
              className="w-full h-24 object-cover rounded-lg"
            />
          ))}
        </div>
      )}

      {/* Vendor Reply */}
      {review.vendorReply && (
        <div className="mt-4 bg-gray-50 border-l-4 border-primary p-4 rounded">
          <div className="flex items-center gap-2 mb-2">
            <svg className="w-5 h-5 text-primary" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" />
            </svg>
            <h5 className="font-semibold text-gray-900">{t('reply_from_vendor')}</h5>
            <span className="text-sm text-gray-500">
              {formatDistanceToNow(new Date(review.vendorReply.repliedAt), {
                addSuffix: true,
                locale: dateLocale,
              })}
            </span>
          </div>
          <p className="text-gray-700 text-sm">{review.vendorReply.content}</p>
        </div>
      )}

      {/* Helpful Button */}
      <div className="mt-4 pt-4 border-t border-gray-200">
        <button
          onClick={handleMarkHelpful}
          disabled={isMarkingHelpful}
          className="flex items-center gap-2 text-sm text-gray-600 hover:text-primary transition-colors disabled:opacity-50 cursor-pointer disabled:cursor-not-allowed"
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"
            />
          </svg>
          {t('helpful')} ({review.helpfulCount})
        </button>
      </div>
    </div>
  );
}

// Memoize to prevent unnecessary re-renders
const ReviewCard = memo(ReviewCardComponent);
export default ReviewCard;
