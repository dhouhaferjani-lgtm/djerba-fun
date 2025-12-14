<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\VendorProfileResource\Pages;

use App\Filament\Admin\Resources\VendorProfileResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVendorProfile extends CreateRecord
{
    protected static string $resource = VendorProfileResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
