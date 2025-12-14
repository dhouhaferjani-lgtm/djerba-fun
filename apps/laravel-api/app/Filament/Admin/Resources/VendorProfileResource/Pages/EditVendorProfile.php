<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\VendorProfileResource\Pages;

use App\Filament\Admin\Resources\VendorProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVendorProfile extends EditRecord
{
    protected static string $resource = VendorProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
