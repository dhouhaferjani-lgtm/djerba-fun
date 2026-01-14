<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\AvailabilityRuleResource\Pages;

use App\Filament\Vendor\Resources\AvailabilityRuleResource;
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
