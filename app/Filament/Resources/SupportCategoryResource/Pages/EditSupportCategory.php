<?php

namespace App\Filament\Resources\SupportCategoryResource\Pages;

use App\Filament\Resources\SupportCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupportCategory extends EditRecord
{
    protected static string $resource = SupportCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
