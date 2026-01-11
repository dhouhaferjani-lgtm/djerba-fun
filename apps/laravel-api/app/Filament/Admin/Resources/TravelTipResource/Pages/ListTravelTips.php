<?php

namespace App\Filament\Admin\Resources\TravelTipResource\Pages;

use App\Filament\Admin\Resources\TravelTipResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTravelTips extends ListRecords
{
    protected static string $resource = TravelTipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
