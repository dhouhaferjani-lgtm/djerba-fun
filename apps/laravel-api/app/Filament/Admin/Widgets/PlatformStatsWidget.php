<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Enums\BookingStatus;
use App\Enums\ListingStatus;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalRevenue = (float) Booking::where('status', BookingStatus::CONFIRMED)
            ->sum('total_amount');

        $monthlyRevenue = (float) Booking::where('status', BookingStatus::CONFIRMED)
            ->whereMonth('created_at', now()->month)
            ->sum('total_amount');

        return [
            Stat::make(__('filament.widgets.total_users'), User::count())
                ->description(__('filament.widgets.active_platform_users'))
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make(__('filament.widgets.total_listings'), Listing::count())
                ->description(__('filament.widgets.published', ['count' => Listing::where('status', ListingStatus::PUBLISHED)->count()]))
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('info'),

            Stat::make(__('filament.widgets.total_bookings'), Booking::count())
                ->description(__('filament.widgets.confirmed', ['count' => Booking::where('status', BookingStatus::CONFIRMED)->count()]))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning'),

            Stat::make(__('filament.widgets.total_revenue'), '$' . number_format($totalRevenue, 2))
                ->description(__('filament.widgets.this_month', ['amount' => '$' . number_format($monthlyRevenue, 2)]))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
        ];
    }
}
