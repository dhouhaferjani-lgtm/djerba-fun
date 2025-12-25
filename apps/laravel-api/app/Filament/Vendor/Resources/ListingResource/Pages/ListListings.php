<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\ListingResource\Pages;

use App\Filament\Vendor\Resources\ListingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Concerns\Translatable;

class ListListings extends ListRecords
{
    use Translatable;

    protected static string $resource = ListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make()
                ->label('Create Listing'),
        ];
    }
}
