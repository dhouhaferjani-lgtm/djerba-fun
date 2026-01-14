<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\ExtraResource\Pages;

use App\Filament\Vendor\Resources\ExtraResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExtra extends CreateRecord
{
    protected static string $resource = ExtraResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['vendor_id'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
