<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Resources\ReviewResource\Pages;

use App\Filament\Vendor\Resources\ReviewResource;
use Filament\Resources\Pages\ListRecords;

class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;
}
