<?php

namespace App\Filament\Resources\QuestionOfferResource\Pages;

use App\Filament\Resources\QuestionOfferResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuestionOffer extends EditRecord
{
    protected static string $resource = QuestionOfferResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
