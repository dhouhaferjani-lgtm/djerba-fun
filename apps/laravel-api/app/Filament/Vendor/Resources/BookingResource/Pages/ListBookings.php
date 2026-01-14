<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\BookingResource\Pages;

use App\Filament\Vendor\Resources\BookingResource;
use Filament\Resources\Pages\ListRecords;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
