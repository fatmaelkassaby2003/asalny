<?php

namespace App\Filament\Resources\ChatResource\Pages;

use App\Filament\Resources\ChatResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChat extends EditRecord
{
    protected static string $resource = ChatResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
