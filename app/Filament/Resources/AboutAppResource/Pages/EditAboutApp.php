<?php

namespace App\Filament\Resources\AboutAppResource\Pages;

use App\Filament\Resources\AboutAppResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditAboutApp extends EditRecord
{
    protected static string $resource = AboutAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }

    public function getTitle(): string
    {
        return 'تعديل عن التطبيق';
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