<?php

namespace App\Filament\Resources\TermsAndConditionsResource\Pages;

use App\Filament\Resources\TermsAndConditionsResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateTermsAndConditions extends CreateRecord
{
    protected static string $resource = TermsAndConditionsResource::class;

    public function getTitle(): string
    {
        return 'إضافة الشروط والأحكام';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('تم الإضافة بنجاح')
            ->body('تم إضافة الشروط والأحكام بنجاح.')
            ->send();
    }
}
