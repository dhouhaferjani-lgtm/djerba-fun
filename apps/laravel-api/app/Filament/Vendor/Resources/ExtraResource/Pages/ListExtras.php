<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\ExtraResource\Pages;

use App\Filament\Vendor\Resources\ExtraResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExtras extends ListRecords
{
    protected static string $resource = ExtraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
