<?php

namespace App\Filament\Resources\AboutAppResource\Pages;

use App\Filament\Resources\AboutAppResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateAboutApp extends CreateRecord
{
    protected static string $resource = AboutAppResource::class;

    public function getTitle(): string
    {
        return 'إضافة عن التطبيق';
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
            ->body('تم إضافة بيانات التطبيق بنجاح.')
            ->send();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // يمكنك إضافة أي تعديلات على البيانات قبل الحفظ هنا
        return $data;
    }
}