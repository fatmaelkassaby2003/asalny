<?php

namespace App\Filament\Resources\PrivacyPolicyResource\Pages;

use App\Filament\Resources\PrivacyPolicyResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreatePrivacyPolicy extends CreateRecord
{
    protected static string $resource = PrivacyPolicyResource::class;

    public function getTitle(): string
    {
        return 'إضافة سياسة الخصوصية';
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
            ->body('تم إضافة سياسة الخصوصية بنجاح.')
            ->send();
    }
}
