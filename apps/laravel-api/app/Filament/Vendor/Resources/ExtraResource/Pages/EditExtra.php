<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\ExtraResource\Pages;

use App\Filament\Vendor\Resources\ExtraResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExtra extends EditRecord
{
    protected static string $resource = ExtraResource::class;

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
