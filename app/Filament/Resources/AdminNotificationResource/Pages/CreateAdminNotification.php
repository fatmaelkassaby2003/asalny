<?php

namespace App\Filament\Resources\AdminNotificationResource\Pages;

use App\Filament\Resources\AdminNotificationResource;
use App\Models\User;
use App\Models\Notification;
use App\Services\FirebaseService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification as FilamentNotification;

class CreateAdminNotification extends CreateRecord
{
    protected static string $resource = AdminNotificationResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $sendTo = $data['send_to'];
        $title = $data['title'];
        $body = $data['body'];
        $type = $data['type'];
        
        $firebase = app(FirebaseService::class);
        
        if ($sendTo === 'all') {
            // إرسال لكل المستخدمين
            $users = User::whereNotNull('fcm_token')->get();
            
            foreach ($users as $user) {
                // إرسال FCM
                $firebase->sendToUser(
                    $user->fcm_token,
                    $title,
                    $body,
                    ['type' => $type]
                );
                
                // حفظ في قاعدة البيانات
                Notification::create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'title' => $title,
                    'body' => $body,
                    'data' => ['type' => $type],
                ]);
            }
            
            FilamentNotification::make()
                ->title('تم إرسال الإشعارات')
                ->success()
                ->body("تم إرسال الإشعار إلى {$users->count()} مستخدم")
                ->send();
                
        } else {
            // إرسال لمستخدم محدد
            $user = User::find($data['user_id']);
            
            if ($user && $user->fcm_token) {
                // إرسال FCM
                $firebase->sendToUser(
                    $user->fcm_token,
                    $title,
                    $body,
                    [' type' => $type]
                );
            }
            
            // حفظ في قاعدة البيانات
            Notification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'data' => ['type' => $type],
            ]);
            
            FilamentNotification::make()
                ->title('تم إرسال الإشعار')
                ->success()
                ->body("تم إرسال الإشعار إلى {$user->name}")
                ->send();
        }
        
        // إرجاع البيانات للحفظ (آخر إشعار تم إرساله)
        return [
            'user_id' => $sendTo === 'all' ? $users->first()->id : $data['user_id'],
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => ['type' => $type],
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
