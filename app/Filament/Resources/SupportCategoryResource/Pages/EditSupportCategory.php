<?php

namespace App\Filament\Resources\SupportCategoryResource\Pages;

use App\Filament\Resources\SupportCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditSupportCategory extends EditRecord
{
    protected static string $resource = SupportCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('حذف')
                ->modalHeading('حذف نوع مشكلة')
                ->modalDescription('هل أنت متأكد من حذف هذا النوع؟ سيؤثر هذا على البلاغات المرتبطة به.'),
        ];
    }

    public function getTitle(): string
    {
        return 'تعديل نوع المشكلة';
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
            ->body('تم تحديث بيانات نوع المشكلة بنجاح.')
            ->send();
    }
}
