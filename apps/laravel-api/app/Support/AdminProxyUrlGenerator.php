<?php

declare(strict_types=1);

namespace App\Support;

use Filament\Facades\Filament;
use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;

class AdminProxyUrlGenerator extends DefaultUrlGenerator
{
    public function getUrl(): string
    {
        if (Filament::isServing()) {
            return route('admin.media.proxy', ['media' => $this->media->id]);
        }

        return parent::getUrl();
    }
}
