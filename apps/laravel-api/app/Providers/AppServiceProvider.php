<?php

namespace App\Providers;

use App\Http\Responses\LogoutResponse;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Listing;
use App\Models\Payout;
use App\Models\Review;
use App\Models\User;
use App\Policies\BookingPolicy;
use App\Policies\CouponPolicy;
use App\Policies\ListingPolicy;
use App\Policies\PayoutPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\UserPolicy;
use App\Services\Payment\ClickToPayPaymentGateway;
use App\Services\Payment\MockPaymentGateway;
use App\Services\Payment\OfflinePaymentGateway;
use App\Services\Payment\PaymentGatewayManager;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Listing::class => ListingPolicy::class,
        Booking::class => BookingPolicy::class,
        Review::class => ReviewPolicy::class,
        Payout::class => PayoutPolicy::class,
        Coupon::class => CouponPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register PaymentGatewayManager as singleton
        $this->app->singleton(PaymentGatewayManager::class, function ($app) {
            $manager = new PaymentGatewayManager;

            // Register payment gateways
            $manager->register('mock', new MockPaymentGateway);
            $manager->register('offline', new OfflinePaymentGateway);
            $manager->register('clicktopay', new ClickToPayPaymentGateway);
            // Note: Stripe gateway will be added when Stripe integration is implemented

            return $manager;
        });

        // Register custom logout response to ensure proper session invalidation
        $this->app->bind(LogoutResponseContract::class, LogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production (behind reverse proxy)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Register policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }
}
