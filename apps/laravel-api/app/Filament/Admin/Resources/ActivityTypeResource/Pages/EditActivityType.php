<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ActivityTypeResource\Pages;

use App\Filament\Admin\Resources\ActivityTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActivityType extends EditRecord
{
    use EditRecord\Concerns\Translatable;

    protected static string $resource = ActivityTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make()
                ->before(function () {
                    if ($this->record->listings_count > 0) {
                        throw new \Exception("Cannot delete activity type with {$this->record->listings_count} existing listing(s).");
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
