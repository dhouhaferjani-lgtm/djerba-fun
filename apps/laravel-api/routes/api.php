<?php

use App\Http\Controllers\Api\Agent\AgentBookingController;
use App\Http\Controllers\Api\Agent\AgentListingController;
use App\Http\Controllers\Api\Agent\AgentSearchController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\BlogPostController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\HoldController;
use App\Http\Controllers\Api\V1\ListingController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check endpoints (no auth required)
Route::get('/health', [HealthController::class, 'index']);
Route::get('/health/detailed', [HealthController::class, 'detailed'])->middleware('auth:sanctum');

// Version prefix for API
Route::prefix('v1')->group(function () {
    // Authentication routes (public)
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Public listing routes
    Route::get('/listings', [ListingController::class, 'index']);
    Route::get('/listings/{listing:slug}', [ListingController::class, 'show']);

    // Public review routes
    Route::get('/listings/{listing:slug}/reviews', [ReviewController::class, 'index']);

    // Public CMS page routes
    Route::get('/pages', [PageController::class, 'index']);
    Route::get('/pages/{slug}', [PageController::class, 'show']);
    Route::get('/pages/code/{code}', [PageController::class, 'showByCode']);

    // Public blog routes
    Route::get('/blog/posts', [BlogPostController::class, 'index']);
    Route::get('/blog/posts/featured', [BlogPostController::class, 'featured']);
    Route::get('/blog/posts/{slug}', [BlogPostController::class, 'show']);
    Route::get('/blog/posts/{slug}/related', [BlogPostController::class, 'related']);
    Route::get('/menus/{menuCode}', [PageController::class, 'getMenu']);

    // Availability routes (public - anyone can view availability)
    Route::get('/listings/{listing:slug}/availability', [AvailabilityController::class, 'index']);
    Route::post('/listings/{listing:slug}/availability/refresh', [AvailabilityController::class, 'refresh']);

    // Booking holds (public - allows guest checkout with session_id)
    Route::post('/listings/{listing:slug}/holds', [HoldController::class, 'store']);
    Route::get('/listings/{listing:slug}/holds/{hold}', [HoldController::class, 'show']);
    Route::delete('/listings/{listing:slug}/holds/{hold}', [HoldController::class, 'destroy']);

    // Direct hold access by ID (for checkout page persistence)
    Route::get('/holds/{hold}', [HoldController::class, 'showById']);

    // Guest booking flow (public - allows guest checkout with session_id)
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::post('/bookings/{booking}/pay', [PaymentController::class, 'processPayment']);
    Route::get('/bookings/{booking}/payment-status', [PaymentController::class, 'paymentStatus']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth routes (authenticated)
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Booking management (authenticated users only)
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::get('/bookings/{booking}', [BookingController::class, 'show']);
        Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);

        // Review management
        Route::post('/bookings/{booking}/review', [ReviewController::class, 'store']);
        Route::post('/reviews/{review}/helpful', [ReviewController::class, 'markHelpful']);

        // Coupon validation
        Route::post('/coupons/validate', [CouponController::class, 'validate']);

        // Additional protected routes will be added in later phases
        // - User profile updates
        // - Vendor listing management
        // - etc.
    });
});

// Agent API routes (authenticated via X-Agent-Key and X-Agent-Secret headers)
Route::prefix('agent')->middleware(['agent.auth', 'agent.audit'])->group(function () {
    // Listing endpoints
    Route::get('listings', [AgentListingController::class, 'index']);
    Route::get('listings/{listing}', [AgentListingController::class, 'show']);
    Route::get('listings/{listing}/availability', [AgentListingController::class, 'availability']);

    // Booking endpoints
    Route::post('bookings', [AgentBookingController::class, 'store']);
    Route::get('bookings/{booking}', [AgentBookingController::class, 'show']);
    Route::post('bookings/{booking}/cancel', [AgentBookingController::class, 'cancel']);

    // Search endpoint
    Route::post('search', [AgentSearchController::class, 'search']);
});

// Public product feeds (no auth required)
Route::prefix('feeds')->group(function () {
    Route::get('listings.json', [FeedController::class, 'listingsJson']);
    Route::get('listings.csv', [FeedController::class, 'listingsCsv']);
    Route::get('availability.json', [FeedController::class, 'availabilityJson']);
});
