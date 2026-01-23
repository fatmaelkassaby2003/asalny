<?php

namespace App\Filament\Resources\TermsAndConditionsResource\Pages;

use App\Filament\Resources\TermsAndConditionsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTermsAndConditions extends EditRecord
{
    protected static string $resource = TermsAndConditionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('حذف'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
