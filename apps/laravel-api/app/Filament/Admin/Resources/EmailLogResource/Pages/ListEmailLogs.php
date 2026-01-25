<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\EmailLogResource\Pages;

use App\Filament\Admin\Resources\EmailLogResource;
use Filament\Resources\Pages\ListRecords;

class ListEmailLogs extends ListRecords
{
    protected static string $resource = EmailLogResource::class;
}
