<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can create review for completed booking.
     */
    public function test_user_can_create_review_for_completed_booking(): void
    {
        // Arrange
        $user = $this->createUser();
        $listing = Listing::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
            'status' => BookingStatus::COMPLETED,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/bookings/{$booking->id}/review", [
                'rating' => 5,
                'title' => 'Amazing experience!',
                'comment' => 'Had a wonderful time on this tour.',
                'would_recommend' => true,
            ]);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'review' => [
                    'id',
                    'rating',
                    'title',
                    'comment',
                    'would_recommend',
                ],
            ]);

        $this->assertDatabaseHas('reviews', [
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'listing_id' => $listing->id,
            'rating' => 5,
        ]);
    }

    /**
     * Test user cannot review booking that is not completed.
     */
    public function test_user_cannot_review_uncompleted_booking(): void
    {
        // Arrange
        $user = $this->createUser();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::CONFIRMED,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/bookings/{$booking->id}/review", [
                'rating' => 5,
                'comment' => 'Great!',
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Can only review completed bookings',
            ]);
    }

    /**
     * Test user cannot review someone else's booking.
     */
    public function test_user_cannot_review_others_booking(): void
    {
        // Arrange
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $booking = Booking::factory()->create([
            'user_id' => $user1->id,
            'status' => BookingStatus::COMPLETED,
        ]);

        // Act
        $response = $this->actingAs($user2)
            ->postJson("/api/v1/bookings/{$booking->id}/review", [
                'rating' => 5,
                'comment' => 'Great!',
            ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test user cannot submit duplicate review.
     */
    public function test_user_cannot_submit_duplicate_review(): void
    {
        // Arrange
        $user = $this->createUser();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::COMPLETED,
        ]);

        Review::factory()->create([
            'booking_id' => $booking->id,
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/bookings/{$booking->id}/review", [
                'rating' => 5,
                'comment' => 'Great!',
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'You have already reviewed this booking',
            ]);
    }

    /**
     * Test rating validation requires value between 1 and 5.
     */
    public function test_rating_validation_requires_value_between_1_and_5(): void
    {
        // Arrange
        $user = $this->createUser();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => BookingStatus::COMPLETED,
        ]);

        // Act - Rating too low
        $response1 = $this->actingAs($user)
            ->postJson("/api/v1/bookings/{$booking->id}/review", [
                'rating' => 0,
                'comment' => 'Bad',
            ]);

        // Act - Rating too high
        $response2 = $this->actingAs($user)
            ->postJson("/api/v1/bookings/{$booking->id}/review", [
                'rating' => 6,
                'comment' => 'Amazing',
            ]);

        // Assert
        $this->assertValidationErrors($response1, ['rating']);
        $this->assertValidationErrors($response2, ['rating']);
    }

    /**
     * Test user can view reviews for listing.
     */
    public function test_user_can_view_reviews_for_listing(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        Review::factory()->count(5)->create([
            'listing_id' => $listing->id,
            'is_approved' => true,
        ]);

        // Create unapproved review (should not be returned)
        Review::factory()->create([
            'listing_id' => $listing->id,
            'is_approved' => false,
        ]);

        // Act
        $response = $this->getJson("/api/v1/listings/{$listing->slug}/reviews");

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(5, 'reviews')
            ->assertJsonStructure([
                'reviews' => [
                    '*' => [
                        'id',
                        'rating',
                        'title',
                        'comment',
                        'user',
                        'created_at',
                    ],
                ],
                'stats' => [
                    'average_rating',
                    'total_reviews',
                ],
            ]);
    }

    /**
     * Test user can mark review as helpful.
     */
    public function test_user_can_mark_review_as_helpful(): void
    {
        // Arrange
        $user = $this->createUser();
        $review = Review::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/reviews/{$review->id}/helpful");

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('review_helpful', [
            'review_id' => $review->id,
            'user_id' => $user->id,
        ]);

        $review->refresh();
        $this->assertEquals(1, $review->helpful_count);
    }

    /**
     * Test user can remove helpful mark.
     */
    public function test_user_can_remove_helpful_mark(): void
    {
        // Arrange
        $user = $this->createUser();
        $review = Review::factory()->create();

        // Mark as helpful first
        $this->actingAs($user)
            ->postJson("/api/v1/reviews/{$review->id}/helpful");

        // Act - Remove helpful
        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/reviews/{$review->id}/helpful");

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseMissing('review_helpful', [
            'review_id' => $review->id,
            'user_id' => $user->id,
        ]);

        $review->refresh();
        $this->assertEquals(0, $review->helpful_count);
    }

    /**
     * Test vendor can reply to review.
     */
    public function test_vendor_can_reply_to_review(): void
    {
        // Arrange
        $vendor = $this->createUser(\App\Enums\UserRole::VENDOR);
        $listing = Listing::factory()->create([
            'vendor_id' => $vendor->vendorProfile->id,
        ]);
        $review = Review::factory()->create([
            'listing_id' => $listing->id,
        ]);

        // Act
        $response = $this->actingAs($vendor)
            ->postJson("/api/v1/reviews/{$review->id}/reply", [
                'comment' => 'Thank you for your feedback!',
            ]);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'reply' => [
                    'id',
                    'comment',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('review_replies', [
            'review_id' => $review->id,
            'user_id' => $vendor->id,
        ]);
    }

    /**
     * Test non-vendor cannot reply to review.
     */
    public function test_non_vendor_cannot_reply_to_review(): void
    {
        // Arrange
        $user = $this->createUser();
        $review = Review::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/v1/reviews/{$review->id}/reply", [
                'comment' => 'Thanks!',
            ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test review updates listing average rating.
     */
    public function test_review_updates_listing_average_rating(): void
    {
        // Arrange
        $listing = Listing::factory()->create([
            'average_rating' => 0,
            'review_count' => 0,
        ]);

        Review::factory()->create([
            'listing_id' => $listing->id,
            'rating' => 5,
            'is_approved' => true,
        ]);

        Review::factory()->create([
            'listing_id' => $listing->id,
            'rating' => 4,
            'is_approved' => true,
        ]);

        // Act - Recalculate ratings
        $listing->refresh();

        // Assert
        $this->assertEquals(4.5, $listing->average_rating);
        $this->assertEquals(2, $listing->review_count);
    }

    /**
     * Test reviews can be filtered by rating.
     */
    public function test_reviews_can_be_filtered_by_rating(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        Review::factory()->count(3)->create([
            'listing_id' => $listing->id,
            'rating' => 5,
            'is_approved' => true,
        ]);
        Review::factory()->count(2)->create([
            'listing_id' => $listing->id,
            'rating' => 3,
            'is_approved' => true,
        ]);

        // Act
        $response = $this->getJson("/api/v1/listings/{$listing->slug}/reviews?rating=5");

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(3, 'reviews');
    }

    /**
     * Test reviews can be sorted by helpful count.
     */
    public function test_reviews_can_be_sorted_by_helpful_count(): void
    {
        // Arrange
        $listing = Listing::factory()->create();
        $review1 = Review::factory()->create([
            'listing_id' => $listing->id,
            'helpful_count' => 10,
            'is_approved' => true,
        ]);
        $review2 = Review::factory()->create([
            'listing_id' => $listing->id,
            'helpful_count' => 25,
            'is_approved' => true,
        ]);

        // Act
        $response = $this->getJson("/api/v1/listings/{$listing->slug}/reviews?sort=helpful");

        // Assert
        $response->assertStatus(200);
        $reviews = $response->json('reviews');
        $this->assertEquals($review2->id, $reviews[0]['id']);
    }

    /**
     * Test user can update their review.
     */
    public function test_user_can_update_their_review(): void
    {
        // Arrange
        $user = $this->createUser();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 3,
            'comment' => 'It was okay',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->putJson("/api/v1/reviews/{$review->id}", [
                'rating' => 5,
                'comment' => 'Actually, it was great!',
            ]);

        // Assert
        $response->assertStatus(200);

        $review->refresh();
        $this->assertEquals(5, $review->rating);
        $this->assertEquals('Actually, it was great!', $review->comment);
    }

    /**
     * Test user can delete their review.
     */
    public function test_user_can_delete_their_review(): void
    {
        // Arrange
        $user = $this->createUser();
        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/reviews/{$review->id}");

        // Assert
        $response->assertStatus(200);

        $this->assertSoftDeleted('reviews', [
            'id' => $review->id,
        ]);
    }
}
