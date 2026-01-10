<?php

namespace App\Filament\Resources\UserQuestionResource\Pages;

use App\Filament\Resources\UserQuestionResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUserQuestion extends CreateRecord
{
    protected static string $resource = UserQuestionResource::class;
}
