<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\EmailLogResource\Pages;

use App\Filament\Vendor\Resources\EmailLogResource;
use Filament\Resources\Pages\ListRecords;

class ListEmailLogs extends ListRecords
{
    protected static string $resource = EmailLogResource::class;
}
