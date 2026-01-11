<?php

namespace App\Filament\Admin\Resources\TravelTipResource\Pages;

use App\Filament\Admin\Resources\TravelTipResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTravelTip extends EditRecord
{
    protected static string $resource = TravelTipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
