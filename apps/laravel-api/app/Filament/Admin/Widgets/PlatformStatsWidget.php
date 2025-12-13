<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\Listing;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalRevenue = Booking::where('status', 'confirmed')
            ->sum('total_amount');

        $monthlyRevenue = Booking::where('status', 'confirmed')
            ->whereMonth('created_at', now()->month)
            ->sum('total_amount');

        return [
            Stat::make('Total Users', User::count())
                ->description('Active platform users')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Total Listings', Listing::count())
                ->description(Listing::where('status', 'published')->count() . ' published')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('info'),

            Stat::make('Total Bookings', Booking::count())
                ->description(Booking::where('status', 'confirmed')->count() . ' confirmed')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning'),

            Stat::make('Total Revenue', '$' . number_format($totalRevenue, 2))
                ->description('$' . number_format($monthlyRevenue, 2) . ' this month')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
        ];
    }
}
