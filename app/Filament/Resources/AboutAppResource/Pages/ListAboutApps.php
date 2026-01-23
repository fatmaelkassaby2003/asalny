<?php

namespace App\Filament\Resources\AboutAppResource\Pages;

use App\Filament\Resources\AboutAppResource;
use Filament\Resources\Pages\ListRecords;

class ListAboutApps extends ListRecords
{
    protected static string $resource = AboutAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()->label('إضافة'),
        ];
    }
}
