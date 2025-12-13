<?php

namespace App\Filament\Admin\Resources\AvailabilityRuleResource\Pages;

use App\Filament\Admin\Resources\AvailabilityRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAvailabilityRules extends ListRecords
{
    protected static string $resource = AvailabilityRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
