<?php

namespace App\Filament\Admin\Resources\DataDeletionRequestResource\Pages;

use App\Filament\Admin\Resources\DataDeletionRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListDataDeletionRequests extends ListRecords
{
    protected static string $resource = DataDeletionRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
