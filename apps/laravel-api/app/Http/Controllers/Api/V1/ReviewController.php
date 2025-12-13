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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    /**
     * Get reviews for a listing.
     */
    public function index(Request $request, Listing $listing): AnonymousResourceCollection
    {
        $reviews = Review::query()
            ->forListing($listing->id)
            ->published()
            ->with(['user', 'reply.vendor'])
            ->when($request->input('rating'), fn ($q, $rating) => $q->withRating((int) $rating))
            ->when(
                $request->input('sort') === 'helpful',
                fn ($q) => $q->mostHelpful(),
                fn ($q) => $q->latest()
            )
            ->paginate($request->input('per_page', 15));

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

        // Check if booking is completed
        if ($booking->status !== BookingStatus::CONFIRMED) {
            return response()->json([
                'message' => 'You can only review completed bookings.',
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
            'is_published' => false, // Admin approval required
        ]);

        // Update listing rating
        $this->updateListingRating($booking->listing);

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
     * Update listing rating based on published reviews.
     */
    private function updateListingRating(Listing $listing): void
    {
        $stats = Review::query()
            ->forListing($listing->id)
            ->published()
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total_reviews')
            ->first();

        $listing->update([
            'rating' => $stats->avg_rating ? round($stats->avg_rating, 2) : null,
            'reviews_count' => $stats->total_reviews,
        ]);
    }
}
