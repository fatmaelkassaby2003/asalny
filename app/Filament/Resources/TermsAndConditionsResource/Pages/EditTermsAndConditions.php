<?php

namespace App\Filament\Resources\TermsAndConditionsResource\Pages;

use App\Filament\Resources\TermsAndConditionsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditTermsAndConditions extends EditRecord
{
    protected static string $resource = TermsAndConditionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }

    public function getTitle(): string
    {
        return 'تعديل الشروط والأحكام';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('تم التحديث بنجاح')
            ->send();
    }
}
