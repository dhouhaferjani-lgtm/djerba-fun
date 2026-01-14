<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\BookingResource\Pages;

use App\Filament\Vendor\Resources\BookingResource;
use Filament\Resources\Pages\Page;

class ManageEventBookings extends Page
{
    protected static string $resource = BookingResource::class;

    protected static string $view = 'filament.vendor.resources.booking-resource.pages.manage-event-bookings';

    public function mount(int $slot): void
    {
        // Mount logic for managing event bookings by slot
    }
}
