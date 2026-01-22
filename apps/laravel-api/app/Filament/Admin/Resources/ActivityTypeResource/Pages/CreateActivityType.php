<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ActivityTypeResource\Pages;

use App\Filament\Admin\Resources\ActivityTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateActivityType extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = ActivityTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
