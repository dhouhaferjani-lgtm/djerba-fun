<?php

namespace App\Filament\Admin\Resources\AvailabilityRuleResource\Pages;

use App\Filament\Admin\Resources\AvailabilityRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAvailabilityRule extends EditRecord
{
    protected static string $resource = AvailabilityRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
