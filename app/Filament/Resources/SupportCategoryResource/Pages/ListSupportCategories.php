<?php

namespace App\Filament\Resources\SupportCategoryResource\Pages;

use App\Filament\Resources\SupportCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupportCategories extends ListRecords
{
    protected static string $resource = SupportCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
