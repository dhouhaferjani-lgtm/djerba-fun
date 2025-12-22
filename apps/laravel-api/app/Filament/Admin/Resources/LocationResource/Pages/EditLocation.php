<?php

namespace App\Filament\Admin\Resources\LocationResource\Pages;

use App\Filament\Admin\Resources\LocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLocation extends EditRecord
{
    use EditRecord\Concerns\Translatable;

    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make()
                ->before(function ($record) {
                    // Prevent deletion if location has listings
                    if ($record->listings_count > 0) {
                        throw new \Exception("Cannot delete location with {$record->listings_count} existing listing(s). Please reassign or delete them first.");
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
