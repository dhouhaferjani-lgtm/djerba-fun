<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ActivityTypeResource\Pages;

use App\Filament\Admin\Resources\ActivityTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivityTypes extends ListRecords
{
    use ListRecords\Concerns\Translatable;

    protected static string $resource = ActivityTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }
}
