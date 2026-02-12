<?php

use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\Partner\PartnerBookingController;
use App\Http\Controllers\Api\Partner\PartnerDashboardController;
use App\Http\Controllers\Api\Partner\PartnerListingController;
use App\Http\Controllers\Api\Partner\PartnerPaymentController;
use App\Http\Controllers\Api\Partner\PartnerSearchController;
use App\Http\Controllers\Api\Partner\PartnerTransactionController;
use App\Http\Controllers\Api\V1\ActivityTypeController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OAuthController;
use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\BlogPostController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\CustomTripRequestController;
use App\Http\Controllers\Api\V1\CartCheckoutController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CheckInController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\ConsentController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\HoldController;
use App\Http\Controllers\Api\V1\ListingController;
use App\Http\Controllers\Api\V1\ListingExtrasController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\MagicLinkController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\BulkParticipantController;
use App\Http\Controllers\Api\V1\ParticipantController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PlatformSettingsController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\RouteProxyController;
use App\Http\Controllers\Api\V1\TravelTipController;
use App\Http\Controllers\Api\V1\CategoryStatsController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\VoucherController;
use App\Http\Controllers\Api\Vendor\VendorVoucherController;
use App\Http\Controllers\Api\Webhooks\MailgunWebhookController;
use App\Http\Controllers\Api\Webhooks\ResendWebhookController;
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

// Webhook endpoints (no auth, signature verified internally)
Route::post('/webhooks/mailgun', [MailgunWebhookController::class, 'handle'])
    ->name('webhooks.mailgun')
    ->middleware('throttle:100,1'); // Rate limit: 100 requests per minute

Route::post('/webhooks/resend', [ResendWebhookController::class, 'handle'])
    ->name('webhooks.resend')
    ->middleware('throttle:100,1'); // Rate limit: 100 requests per minute

// Version prefix for API
Route::prefix('v1')->group(function () {
    // Authentication routes (public) - rate limited to prevent brute force attacks
    Route::post('/auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:3,60'); // 3 attempts per hour
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,15'); // 5 attempts per 15 minutes

    // Magic link authentication (public) - rate limited
    Route::post('/auth/magic-link/send', [AuthController::class, 'sendMagicLink'])
        ->middleware('throttle:3,60'); // 3 attempts per hour
    Route::post('/auth/magic-link/verify', [AuthController::class, 'verifyMagicLink'])
        ->middleware('throttle:5,15'); // 5 attempts per 15 minutes
    Route::post('/auth/magic-link/register', [AuthController::class, 'registerPasswordless'])
        ->middleware('throttle:3,60'); // 3 attempts per hour

    // Email verification routes (public)
    Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail'])
        ->middleware('throttle:5,15'); // 5 attempts per 15 minutes
    Route::post('/auth/resend-verification', [AuthController::class, 'resendVerification'])
        ->middleware('throttle:3,60'); // 3 attempts per hour

    // OAuth social login routes (public)
    Route::get('/auth/oauth/{provider}/redirect', [OAuthController::class, 'redirect']);
    Route::get('/auth/oauth/{provider}/callback', [OAuthController::class, 'callback']);

    // Map route proxy (OSRM with Redis caching)
    Route::get('/route', RouteProxyController::class);

    // Public listing routes
    Route::get('/listings', [ListingController::class, 'index']);
    Route::get('/listings/featured', [ListingController::class, 'featured']);
    Route::get('/listings/{listing:slug}', [ListingController::class, 'show']);

    // Public location/destination routes
    Route::get('/locations', [LocationController::class, 'index']);
    Route::get('/locations/{slug}', [LocationController::class, 'show']);

    // Public activity type routes (for tour categorization)
    Route::get('/activity-types', [ActivityTypeController::class, 'index']);
    Route::get('/activity-types/{activityType:slug}', [ActivityTypeController::class, 'show']);

    // Public review routes
    Route::get('/listings/{listing:slug}/reviews', [ReviewController::class, 'index']);
    Route::get('/listings/{listing:slug}/reviews/summary', [ReviewController::class, 'summary']);

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

    // Platform settings routes (public - for frontend configuration)
    Route::get('/platform/settings', [PlatformSettingsController::class, 'index']);
    Route::get('/platform/schema', [PlatformSettingsController::class, 'schema']);

    // Travel tips routes (public - for hero section rotating tips)
    Route::get('/travel-tips', [TravelTipController::class, 'index']);

    // Category stats routes (public - for homepage category cards)
    Route::get('/category-stats', [CategoryStatsController::class, 'index']);

    // Custom trip request routes (public - anyone can submit a request)
    Route::post('/custom-trip-requests', [CustomTripRequestController::class, 'store']);

    // Contact form route (public - rate limited)
    Route::post('/contact', [ContactController::class, 'store'])
        ->middleware('throttle:5,1'); // 5 submissions per minute

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

    // Cart routes (supports both authenticated users and guest checkout with session_id)
    // Uses optional.auth middleware to link user_id when authenticated
    Route::middleware('optional.auth')->group(function () {
        Route::get('/cart', [CartController::class, 'show']);
        Route::post('/cart/items', [CartController::class, 'addItem']);
        Route::delete('/cart/items/{item}', [CartController::class, 'removeItem']);
        Route::patch('/cart/items/{item}', [CartController::class, 'updateItem']);
        Route::delete('/cart', [CartController::class, 'clear']);
        Route::get('/cart/summary', [CartController::class, 'summary']);
        Route::post('/cart/extend-holds', [CartController::class, 'extendHolds']);

        // Cart checkout routes
        Route::post('/cart/checkout', [CartCheckoutController::class, 'initiateCheckout']);
        Route::post('/cart/checkout/{payment}/pay', [CartCheckoutController::class, 'processPayment']);
        Route::get('/cart/checkout/{payment}/status', [CartCheckoutController::class, 'status']);
        Route::post('/cart/checkout/cancel', [CartCheckoutController::class, 'cancelCheckout']);
    });

    // Checkout endpoints (public - for billing verification and pricing)
    Route::prefix('checkout')->group(function () {
        Route::post('/verify-billing', [CheckoutController::class, 'verifyBilling']);
    });

    // Payment methods (public)
    Route::get('/payment/methods', [PaymentController::class, 'availableMethods']);

    // Booking creation and payment (supports both authenticated users and guest checkout)
    // Uses optional.auth middleware to link user_id when authenticated
    Route::post('/bookings', [BookingController::class, 'store'])
        ->middleware('optional.auth');
    Route::post('/bookings/{booking}/pay', [PaymentController::class, 'processPayment'])
        ->middleware('optional.auth');
    Route::get('/bookings/{booking}/payment-status', [PaymentController::class, 'paymentStatus']);

    // Guest booking access (via session_id header or query param)
    // These routes allow guests to access their bookings without authentication
    Route::get('/bookings/{booking}/guest', [BookingController::class, 'showGuest']);
    Route::get('/bookings/{booking}/participants/guest', [ParticipantController::class, 'indexGuest']);
    Route::put('/bookings/{booking}/participants/guest', [ParticipantController::class, 'updateGuest']);
    Route::get('/bookings/{booking}/vouchers/guest', [VoucherController::class, 'indexGuest']);

    // Bulk participant update for cart checkout (guest access via session_id)
    Route::post('/bookings/participants/bulk-apply/guest', [BulkParticipantController::class, 'bulkApplyGuest'])
        ->middleware('optional.auth');

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

        // User profile management
        Route::get('/me', [UserController::class, 'show']);
        Route::put('/me', [UserController::class, 'update']);
        Route::put('/me/password', [UserController::class, 'updatePassword']);
        Route::post('/me/avatar', [UserController::class, 'uploadAvatar']);
        Route::delete('/me/avatar', [UserController::class, 'deleteAvatar']);
        Route::get('/me/preferences', [UserController::class, 'getPreferences']);
        Route::put('/me/preferences', [UserController::class, 'updatePreferences']);
        Route::get('/me/export', [UserController::class, 'export']);
        Route::delete('/me', [UserController::class, 'destroy']);

        // Booking management (authenticated users only)
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::get('/bookings/{booking}', [BookingController::class, 'show']);
        Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);

        // Booking linking (authenticated users only)
        // Rate limited to prevent brute-force booking number guessing
        Route::get('/bookings/claimable', [BookingController::class, 'claimable'])
            ->middleware('throttle:10,1');
        Route::post('/bookings/link', [BookingController::class, 'link'])
            ->middleware('throttle:10,1');
        Route::post('/bookings/claim', [BookingController::class, 'claim'])
            ->middleware('throttle:5,1'); // Stricter limit for claim attempts

        // Review management
        Route::get('/listings/{listing:slug}/can-review', [ReviewController::class, 'canReview']);
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

        // Bulk participant update for cart checkout (applies same names to multiple bookings)
        Route::post('/bookings/participants/bulk-apply', [BulkParticipantController::class, 'bulkApply']);

        // Voucher access
        Route::get('/bookings/{booking}/vouchers', [VoucherController::class, 'index']);
        Route::get('/bookings/{booking}/vouchers/{voucherCode}', [VoucherController::class, 'show']);

        // Voucher PDF download
        Route::get('/bookings/{booking}/vouchers/pdf', [VoucherController::class, 'downloadAll']);
        Route::get('/bookings/{booking}/vouchers/{voucherCode}/pdf', [VoucherController::class, 'downloadSingle']);

        // Voucher email
        Route::post('/bookings/{booking}/vouchers/email', [VoucherController::class, 'emailAll']);
        Route::post('/bookings/{booking}/vouchers/{voucherCode}/email', [VoucherController::class, 'emailSingle']);

        // Voucher lookup by code (for convenience)
        Route::get('/vouchers/{voucherCode}', [ParticipantController::class, 'showByVoucherCode']);

        // Vendor check-in endpoints
        // Bulk check-in endpoints (must come before parameterized routes)
        Route::post('/check-in/bulk', [CheckInController::class, 'bulkCheckIn']);
        Route::post('/check-in/bulk/undo', [CheckInController::class, 'bulkUndoCheckIn']);

        // Individual check-in endpoints
        Route::post('/check-in/{voucherCode}', [CheckInController::class, 'checkIn']);
        Route::delete('/check-in/{voucherCode}', [CheckInController::class, 'undoCheckIn']);
        Route::get('/check-in/{voucherCode}/lookup', [CheckInController::class, 'lookup']);

        // Vendor-specific voucher management
        // These routes allow vendors to download/email vouchers for their own listings
        Route::prefix('vendor')->group(function () {
            Route::get('/bookings/{booking}/vouchers/download', [VendorVoucherController::class, 'download']);
            Route::post('/bookings/{booking}/vouchers/email', [VendorVoucherController::class, 'email']);
        });

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
