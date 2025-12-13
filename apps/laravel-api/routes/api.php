<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\HoldController;
use App\Http\Controllers\Api\V1\ListingController;
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

// Health check endpoint (no auth required)
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'version' => config('app.version', '0.1.0'),
    ]);
});

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

    // Availability routes (public - anyone can view availability)
    Route::get('/listings/{listing:slug}/availability', [AvailabilityController::class, 'index']);
    Route::post('/listings/{listing:slug}/availability/refresh', [AvailabilityController::class, 'refresh']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth routes (authenticated)
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Booking holds (require authentication)
        Route::post('/listings/{listing:slug}/holds', [HoldController::class, 'store']);
        Route::get('/listings/{listing:slug}/holds/{hold}', [HoldController::class, 'show']);
        Route::delete('/listings/{listing:slug}/holds/{hold}', [HoldController::class, 'destroy']);

        // Booking management
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::get('/bookings/{booking}', [BookingController::class, 'show']);
        Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);

        // Payment management
        Route::post('/bookings/{booking}/pay', [PaymentController::class, 'processPayment']);
        Route::get('/bookings/{booking}/payment-status', [PaymentController::class, 'paymentStatus']);

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
