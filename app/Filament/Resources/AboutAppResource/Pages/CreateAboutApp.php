<?php

namespace App\Filament\Resources\AboutAppResource\Pages;

use App\Filament\Resources\AboutAppResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAboutApp extends CreateRecord
{
    protected static string $resource = AboutAppResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
