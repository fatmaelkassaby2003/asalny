<?php

namespace App\Filament\Resources\SupportCategoryResource\Pages;

use App\Filament\Resources\SupportCategoryResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateSupportCategory extends CreateRecord
{
    protected static string $resource = SupportCategoryResource::class;

    public function getTitle(): string
    {
        return 'إضافة نوع مشكلة جديد';
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
            ->body('تم إضافة نوع المشكلة بنجاح.')
            ->send();
    }
}
