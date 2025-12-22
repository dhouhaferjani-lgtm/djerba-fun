<?php

use App\Http\Controllers\Api\Partner\PartnerBookingController;
use App\Http\Controllers\Api\Partner\PartnerDashboardController;
use App\Http\Controllers\Api\Partner\PartnerListingController;
use App\Http\Controllers\Api\Partner\PartnerPaymentController;
use App\Http\Controllers\Api\Partner\PartnerSearchController;
use App\Http\Controllers\Api\Partner\PartnerTransactionController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\BlogPostController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\CartCheckoutController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CheckInController;
use App\Http\Controllers\Api\V1\ParticipantController;
use App\Http\Controllers\Api\V1\VoucherController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\HoldController;
use App\Http\Controllers\Api\V1\ListingController;
use App\Http\Controllers\Api\V1\ListingExtrasController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\ConsentController;
use App\Http\Controllers\Api\V1\MagicLinkController;
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

    // Public location/destination routes
    Route::get('/locations', [LocationController::class, 'index']);
    Route::get('/locations/{slug}', [LocationController::class, 'show']);

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

    // Extras routes (public - anyone can view available extras)
    Route::get('/listings/{listing:slug}/extras', [ListingExtrasController::class, 'index']);
    Route::post('/listings/{listing:slug}/extras/calculate', [ListingExtrasController::class, 'calculate']);

    // Booking holds (public - allows guest checkout with session_id)
    Route::post('/listings/{listing:slug}/holds', [HoldController::class, 'store']);
    Route::get('/listings/{listing:slug}/holds/{hold}', [HoldController::class, 'show']);
    Route::delete('/listings/{listing:slug}/holds/{hold}', [HoldController::class, 'destroy']);

    // Direct hold access by ID (for checkout page persistence)
    Route::get('/holds/{hold}', [HoldController::class, 'showById']);

    // Cart routes (public - supports guest checkout with session_id)
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::delete('/cart/items/{item}', [CartController::class, 'removeItem']);
    Route::patch('/cart/items/{item}', [CartController::class, 'updateItem']);
    Route::delete('/cart', [CartController::class, 'clear']);
    Route::get('/cart/summary', [CartController::class, 'summary']);
    Route::post('/cart/extend-holds', [CartController::class, 'extendHolds']);

    // Cart checkout routes (public - supports guest checkout with session_id)
    Route::post('/cart/checkout', [CartCheckoutController::class, 'initiateCheckout']);
    Route::post('/cart/checkout/{payment}/pay', [CartCheckoutController::class, 'processPayment']);
    Route::get('/cart/checkout/{payment}/status', [CartCheckoutController::class, 'status']);
    Route::post('/cart/checkout/cancel', [CartCheckoutController::class, 'cancelCheckout']);

    // Guest booking flow (public - allows guest checkout with session_id)
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::post('/bookings/{booking}/pay', [PaymentController::class, 'processPayment']);
    Route::get('/bookings/{booking}/payment-status', [PaymentController::class, 'paymentStatus']);

    // Guest booking access (via session_id header or query param)
    // These routes allow guests to access their bookings without authentication
    Route::get('/bookings/{booking}/guest', [BookingController::class, 'showGuest']);
    Route::get('/bookings/{booking}/participants/guest', [ParticipantController::class, 'indexGuest']);
    Route::put('/bookings/{booking}/participants/guest', [ParticipantController::class, 'updateGuest']);
    Route::get('/bookings/{booking}/vouchers/guest', [VoucherController::class, 'indexGuest']);

    // Magic link routes (token-based access - no authentication required)
    // These allow guests to access bookings via secure token sent in email
    Route::get('/bookings/magic/{token}', [MagicLinkController::class, 'show']);
    Route::post('/bookings/resend-magic-link', [MagicLinkController::class, 'resend']);
    Route::get('/bookings/magic/{token}/participants', [MagicLinkController::class, 'participants']);
    Route::put('/bookings/magic/{token}/participants', [MagicLinkController::class, 'updateParticipants']);
    Route::get('/bookings/magic/{token}/vouchers', [MagicLinkController::class, 'vouchers']);

    // Consent routes (public - for cookie banner and checkout consent)
    Route::post('/consent', [ConsentController::class, 'store']);
    Route::get('/consent/status', [ConsentController::class, 'status']);
    Route::post('/consent/revoke', [ConsentController::class, 'revoke']);

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

        // Cart merge (merge guest cart into user cart after login)
        Route::post('/cart/merge', [CartController::class, 'merge']);

        // Consent history (authenticated users only)
        Route::get('/consent/history', [ConsentController::class, 'history']);

        // Participant management (for post-checkout name entry)
        Route::get('/bookings/{booking}/participants', [ParticipantController::class, 'index']);
        Route::put('/bookings/{booking}/participants', [ParticipantController::class, 'update']);

        // Voucher access
        Route::get('/bookings/{booking}/vouchers', [VoucherController::class, 'index']);
        Route::get('/bookings/{booking}/vouchers/{voucherCode}', [VoucherController::class, 'show']);

        // Voucher lookup by code (for convenience)
        Route::get('/vouchers/{voucherCode}', [ParticipantController::class, 'showByVoucherCode']);

        // Vendor check-in endpoints
        Route::post('/check-in/{voucherCode}', [CheckInController::class, 'checkIn']);
        Route::delete('/check-in/{voucherCode}', [CheckInController::class, 'undoCheckIn']);
        Route::get('/check-in/{voucherCode}/lookup', [CheckInController::class, 'lookup']);

        // Additional protected routes will be added in later phases
        // - User profile updates
        // - Vendor listing management
        // - etc.
    });
});

// Partner API routes (authenticated via X-Partner-Key and X-Partner-Secret headers)
Route::prefix('partner')->middleware(['partner.auth', 'partner.audit'])->group(function () {
    // Listing endpoints
    Route::get('listings', [PartnerListingController::class, 'index']);
    Route::get('listings/{listing}', [PartnerListingController::class, 'show']);
    Route::get('listings/{listing}/availability', [PartnerListingController::class, 'availability']);

    // Booking endpoints
    Route::get('bookings', [PartnerBookingController::class, 'index']);
    Route::post('bookings', [PartnerBookingController::class, 'store']);
    Route::get('bookings/{booking}', [PartnerBookingController::class, 'show']);
    Route::post('bookings/{booking}/confirm', [PartnerBookingController::class, 'confirm']);
    Route::post('bookings/{booking}/cancel', [PartnerBookingController::class, 'cancel']);

    // Search endpoint
    Route::post('search', [PartnerSearchController::class, 'search']);

    // Dashboard endpoints
    Route::get('dashboard/analytics', [PartnerDashboardController::class, 'analytics']);
    Route::get('dashboard/balance', [PartnerDashboardController::class, 'balance']);

    // Transaction endpoints
    Route::get('transactions', [PartnerTransactionController::class, 'index']);
    Route::get('transactions/{transaction}', [PartnerTransactionController::class, 'show']);

    // Payment endpoints
    Route::post('payments/initiate', [PartnerPaymentController::class, 'initiate']);
    Route::get('payments/history', [PartnerPaymentController::class, 'history']);
});

// Public product feeds (no auth required)
Route::prefix('feeds')->group(function () {
    Route::get('listings.json', [FeedController::class, 'listingsJson']);
    Route::get('listings.csv', [FeedController::class, 'listingsCsv']);
    Route::get('availability.json', [FeedController::class, 'availabilityJson']);
});
