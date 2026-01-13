<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CustomTripRequestResource\Pages;

use App\Filament\Admin\Resources\CustomTripRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomTripRequests extends ListRecords
{
    protected static string $resource = CustomTripRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - requests come from frontend
        ];
    }
}
