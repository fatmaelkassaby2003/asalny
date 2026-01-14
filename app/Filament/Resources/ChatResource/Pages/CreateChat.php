<?php

namespace App\Filament\Resources\ChatResource\Pages;

use App\Filament\Resources\ChatResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateChat extends CreateRecord
{
    protected static string $resource = ChatResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $order = \App\Models\Order::find($data['order_id']);
        
        if ($order) {
            $data['asker_id'] = $order->asker_id;
            $data['answerer_id'] = $order->answerer_id;
        }

        return $data;
    }
}
