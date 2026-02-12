<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Review;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class ReviewController extends Controller
{
    /**
     * Get reviews for a listing.
     *
     * Performance optimizations:
     * - Eager load relationships with specific columns
     * - Select only needed columns from reviews table
     * - Cache reviews for 5 minutes
     */
    public function index(Request $request, Listing $listing): AnonymousResourceCollection
    {
        $rating = $request->input('rating');
        $sort = $request->input('sort', 'latest');
        $perPage = min($request->input('per_page', 15), 50);

        // Build cache key from parameters
        $cacheKey = sprintf(
            'reviews:listing:%s:rating:%s:sort:%s:page:%s',
            $listing->id,
            $rating ?? 'all',
            $sort,
            $request->input('page', 1)
        );
        $cacheTtl = 300; // 5 minutes

        $reviews = cache()->remember($cacheKey, $cacheTtl, function () use ($listing, $rating, $sort, $perPage) {
            return Review::query()
                ->forListing($listing->id)
                ->published()
                ->selectApi() // Use model scope to prevent column mismatch issues
                // Performance: Eager load with specific columns
                ->with([
                    'user:id,uuid,first_name,last_name,display_name,avatar_url',
                    'reply:id,review_id,vendor_id,content,created_at',
                    'reply.vendor:id,uuid'
                ])
                ->when($rating, fn ($q, $r) => $q->withRating((int) $r))
                ->when(
                    $sort === 'helpful',
                    fn ($q) => $q->mostHelpful(),
                    fn ($q) => $q->latest()
                )
                ->paginate($perPage);
        });

        return ReviewResource::collection($reviews);
    }

    /**
     * Create a review for a booking.
     */
    public function store(CreateReviewRequest $request, Booking $booking): JsonResponse
    {
        // Check if booking belongs to authenticated user
        if ($booking->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized to review this booking.',
            ], 403);
        }

        // Check if booking is completed or confirmed
        if (! in_array($booking->status, [BookingStatus::CONFIRMED, BookingStatus::COMPLETED])) {
            return response()->json([
                'message' => 'You can only review confirmed or completed bookings.',
            ], 400);
        }

        // Check if review already exists
        if ($booking->review()->exists()) {
            return response()->json([
                'message' => 'You have already reviewed this booking.',
            ], 400);
        }

        $review = Review::create([
            'booking_id' => $booking->id,
            'listing_id' => $booking->listing_id,
            'user_id' => $request->user()->id,
            'rating' => $request->validated('rating'),
            'title' => $request->validated('title'),
            'content' => $request->validated('content'),
            'pros' => $request->validated('pros'),
            'cons' => $request->validated('cons'),
            'photos' => $request->validated('photos'),
            'is_verified_booking' => true,
            'is_published' => false, // Vendor/admin approval required
        ]);

        // NOTE: Rating is NOT recalculated here — only on approval via Filament
        // Review::recalculateListingRating() is called when vendor approves the review

        // Notify vendor of new review
        try {
            $vendor = $booking->listing?->vendor;
            if ($vendor) {
                $listingTitle = $booking->listing->getTranslation('title', 'en') ?: $booking->listing->getTranslation('title', 'fr') ?: 'Untitled';
                if (is_array($listingTitle)) {
                    $listingTitle = reset($listingTitle) ?: 'Untitled';
                }

                $reviewUrl = \App\Filament\Vendor\Resources\ReviewResource::getUrl('view', ['record' => $review], panel: 'vendor');
                $vendor->notifications()->create([
                    'id' => Str::uuid()->toString(),
                    'type' => \Filament\Notifications\DatabaseNotification::class,
                    'data' => Notification::make()
                        ->title('New Review Received')
                        ->icon('heroicon-o-star')
                        ->body("A {$review->rating}-star review was submitted for \"{$listingTitle}\". Please approve or reject it.")
                        ->actions([
                            NotificationAction::make('view')
                                ->label('View Review')
                                ->url($reviewUrl)
                                ->button(),
                        ])
                        ->getDatabaseMessage(),
                ]);
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to send new review notification to vendor', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'message' => 'Review submitted successfully. It will be published after moderation.',
            'data' => new ReviewResource($review),
        ], 201);
    }

    /**
     * Mark a review as helpful.
     */
    public function markHelpful(Review $review): JsonResponse
    {
        $review->incrementHelpful();

        return response()->json([
            'message' => 'Thank you for your feedback!',
            'helpful_count' => $review->helpful_count,
        ]);
    }

    /**
     * Check if the authenticated user can write a review for a listing.
     */
    public function canReview(Request $request, Listing $listing): JsonResponse
    {
        $user = $request->user();

        $reviewableBooking = Booking::where('user_id', $user->id)
            ->where('listing_id', $listing->id)
            ->whereIn('status', [BookingStatus::CONFIRMED->value, BookingStatus::COMPLETED->value])
            ->whereDoesntHave('review')
            ->first();

        return response()->json([
            'canReview' => $reviewableBooking !== null,
            'bookingId' => $reviewableBooking?->id,
        ]);
    }

    /**
     * Get review summary (rating breakdown) for a listing.
     */
    public function summary(Listing $listing): JsonResponse
    {
        $cacheKey = "reviews:summary:{$listing->id}";

        $summary = cache()->remember($cacheKey, 300, function () use ($listing) {
            $breakdown = Review::query()
                ->forListing($listing->id)
                ->published()
                ->selectRaw('rating, COUNT(*) as count')
                ->groupBy('rating')
                ->pluck('count', 'rating')
                ->toArray();

            $totalReviews = array_sum($breakdown);
            $avgRating = $totalReviews > 0
                ? collect($breakdown)->reduce(fn ($carry, $count, $rating) => $carry + ($rating * $count), 0) / $totalReviews
                : 0;

            return [
                'averageRating' => round($avgRating, 1),
                'totalCount' => $totalReviews,
                'ratingBreakdown' => [
                    5 => $breakdown[5] ?? 0,
                    4 => $breakdown[4] ?? 0,
                    3 => $breakdown[3] ?? 0,
                    2 => $breakdown[2] ?? 0,
                    1 => $breakdown[1] ?? 0,
                ],
            ];
        });

        return response()->json(['data' => $summary]);
    }
}
