'use client';

import { Star, ThumbsUp, MessageSquare } from 'lucide-react';
import { useTranslations } from 'next-intl';
import { Badge } from '@go-adventure/ui';
import type { Review, ReviewSummary } from '@go-adventure/schemas';
import { format } from 'date-fns';

interface ReviewsSectionProps {
  listingId: string;
  rating?: number;
  reviewsCount: number;
  reviews?: Review[];
  summary?: ReviewSummary;
}

export function ReviewsSection({
  listingId,
  rating,
  reviewsCount,
  reviews = [],
  summary,
}: ReviewsSectionProps) {
  const t = useTranslations('listing.reviews');

  // Empty state - no reviews yet
  if (reviewsCount === 0) {
    return (
      <section className="border-t border-neutral-200 pt-12">
        <div className="bg-accent-light border border-accent-dark rounded-2xl p-12 text-center">
          <MessageSquare className="h-16 w-16 mx-auto text-neutral-400 mb-4" />
          <h3 className="font-display text-2xl font-semibold text-heading mb-2">No reviews yet</h3>
          <p className="text-neutral-600 max-w-md mx-auto">
            Be the first to experience this adventure and share your thoughts with fellow travelers!
          </p>
        </div>
      </section>
    );
  }

  // Calculate rating breakdown percentages
  const getRatingPercentage = (count: number) => {
    return reviewsCount > 0 ? (count / reviewsCount) * 100 : 0;
  };

  return (
    <section className="border-t border-neutral-200 pt-12">
      {/* Section Header */}
      <div className="flex items-center gap-3 mb-8">
        <Star className="h-6 w-6 text-primary fill-secondary" />
        <h2 className="font-display text-3xl font-bold text-heading tracking-tight">
          {reviewsCount} {reviewsCount === 1 ? 'Review' : 'Reviews'}
        </h2>
      </div>

      {/* Review Summary */}
      {summary && (
        <div className="grid md:grid-cols-[300px_1fr] gap-8 mb-12">
          {/* Overall Rating */}
          <div className="bg-accent-light rounded-2xl p-6 text-center">
            <div className="text-5xl font-display font-bold text-heading mb-2">
              {summary.averageRating.toFixed(1)}
            </div>
            <div className="flex items-center justify-center gap-1 mb-2">
              {[1, 2, 3, 4, 5].map((star) => (
                <Star
                  key={star}
                  className={`h-5 w-5 ${
                    star <= Math.round(summary.averageRating)
                      ? 'fill-secondary text-secondary'
                      : 'text-neutral-300'
                  }`}
                />
              ))}
            </div>
            <p className="text-sm text-neutral-600">
              Based on {reviewsCount} {reviewsCount === 1 ? 'review' : 'reviews'}
            </p>
          </div>

          {/* Rating Breakdown */}
          <div className="space-y-3">
            {[5, 4, 3, 2, 1].map((stars) => {
              const count = summary.ratingBreakdown[stars as keyof typeof summary.ratingBreakdown];
              const percentage = getRatingPercentage(count);
              return (
                <div key={stars} className="flex items-center gap-3">
                  <span className="text-sm font-medium text-neutral-700 w-12">
                    {stars} star{stars !== 1 && 's'}
                  </span>
                  <div className="flex-1 h-3 bg-neutral-200 rounded-full overflow-hidden">
                    <div
                      className="h-full bg-secondary transition-all duration-300"
                      style={{ width: `${percentage}%` }}
                    />
                  </div>
                  <span className="text-sm text-neutral-600 w-16 text-right">
                    {count} ({Math.round(percentage)}%)
                  </span>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* Review List */}
      {reviews.length > 0 && (
        <div className="space-y-8">
          {reviews.slice(0, 5).map((review) => (
            <ReviewCard key={review.id} review={review} />
          ))}

          {reviews.length > 5 && (
            <button className="text-primary font-semibold hover:underline">
              Show all {reviewsCount} reviews
            </button>
          )}
        </div>
      )}
    </section>
  );
}

// Individual Review Card Component
function ReviewCard({ review }: { review: Review }) {
  return (
    <article className="border-b border-neutral-200 pb-8 last:border-0">
      {/* Reviewer Info */}
      <div className="flex items-start gap-4 mb-4">
        <div className="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
          <span className="text-primary font-semibold text-lg">
            {review.user?.displayName?.charAt(0).toUpperCase() || 'T'}
          </span>
        </div>
        <div className="flex-1">
          <div className="flex items-center justify-between mb-1">
            <h3 className="font-semibold text-heading">{review.user?.displayName || 'Traveler'}</h3>
            <span className="text-sm text-neutral-600">
              {format(new Date(review.createdAt), 'MMM yyyy')}
            </span>
          </div>
          <div className="flex items-center gap-2">
            <div className="flex items-center gap-1">
              {[1, 2, 3, 4, 5].map((star) => (
                <Star
                  key={star}
                  className={`h-4 w-4 ${
                    star <= review.rating ? 'fill-secondary text-secondary' : 'text-neutral-300'
                  }`}
                />
              ))}
            </div>
            {review.isVerified && (
              <Badge variant="success" className="text-xs">
                Verified booking
              </Badge>
            )}
          </div>
        </div>
      </div>

      {/* Review Title */}
      {review.title && <h4 className="font-semibold text-heading mb-2">{review.title}</h4>}

      {/* Review Content */}
      <p className="text-neutral-700 leading-relaxed mb-4">{review.content}</p>

      {/* Pros & Cons */}
      {(review.pros.length > 0 || review.cons.length > 0) && (
        <div className="grid md:grid-cols-2 gap-4 mb-4">
          {review.pros.length > 0 && (
            <div>
              <p className="text-sm font-semibold text-green-700 mb-2">Highlights</p>
              <ul className="space-y-1">
                {review.pros.map((pro, index) => (
                  <li key={index} className="text-sm text-neutral-700 flex items-start gap-2">
                    <span className="text-green-600">✓</span>
                    <span>{pro}</span>
                  </li>
                ))}
              </ul>
            </div>
          )}
          {review.cons.length > 0 && (
            <div>
              <p className="text-sm font-semibold text-neutral-700 mb-2">Could improve</p>
              <ul className="space-y-1">
                {review.cons.map((con, index) => (
                  <li key={index} className="text-sm text-neutral-700 flex items-start gap-2">
                    <span className="text-neutral-400">•</span>
                    <span>{con}</span>
                  </li>
                ))}
              </ul>
            </div>
          )}
        </div>
      )}

      {/* Review Photos */}
      {review.photos.length > 0 && (
        <div className="flex gap-2 mb-4">
          {review.photos.slice(0, 4).map((photo, index) => (
            <div key={index} className="w-24 h-24 rounded-lg overflow-hidden bg-neutral-200">
              <img
                src={photo.url}
                alt={photo.caption || 'Review photo'}
                className="w-full h-full object-cover"
              />
            </div>
          ))}
        </div>
      )}

      {/* Vendor Reply */}
      {review.vendorReply && (
        <div className="bg-accent-light rounded-lg p-4 ml-8 border-l-4 border-primary">
          <div className="flex items-center gap-2 mb-2">
            <span className="text-sm font-semibold text-heading">Response from host</span>
            <span className="text-xs text-neutral-600">
              {format(new Date(review.vendorReply.repliedAt), 'MMM yyyy')}
            </span>
          </div>
          <p className="text-sm text-neutral-700">{review.vendorReply.content}</p>
        </div>
      )}

      {/* Helpful Button */}
      <button className="flex items-center gap-2 text-sm text-neutral-600 hover:text-primary transition-colors mt-4">
        <ThumbsUp className="h-4 w-4" />
        <span>Helpful ({review.helpfulCount})</span>
      </button>
    </article>
  );
}
