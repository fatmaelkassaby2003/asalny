<?php

namespace App\Filament\Resources\AboutAppResource\Pages;

use App\Filament\Resources\AboutAppResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAboutApps extends ListRecords
{
    protected static string $resource = AboutAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة جديد')
                ->icon('heroicon-o-plus')
                ->color('success'),
        ];
    }

    public function getTitle(): string
    {
        return 'عن التطبيق';
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}