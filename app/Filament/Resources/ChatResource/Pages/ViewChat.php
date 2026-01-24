<?php

namespace App\Filament\Resources\ChatResource\Pages;

use App\Filament\Resources\ChatResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewChat extends ViewRecord
{
    protected static string $resource = ChatResource::class;
    
    protected static string $view = 'filament.resources.chat-resource.pages.view-chat';
    
    public function getTitle(): string 
    {
        $chat = $this->record;
        $asker = $chat->order?->asker?->name ?? 'غير محدد';
        $answerer = $chat->order?->answerer?->name ?? 'غير محدد';
        
        return "محادثة: {$asker} ↔ {$answerer}";
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('رجوع للمحادثات')
                ->icon('heroicon-o-arrow-right')
                ->color('gray')
                ->url(ChatResource::getUrl('index')),
                
            Actions\DeleteAction::make()
                ->label('حذف المحادثة')
                ->icon('heroicon-o-trash')
                ->color('danger'),
        ];
    }
}
