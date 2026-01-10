<?php

namespace App\Filament\Resources\UserQuestionResource\Pages;

use App\Filament\Resources\UserQuestionResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserQuestion extends EditRecord
{
    protected static string $resource = UserQuestionResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
