<?php

namespace App\Filament\Resources\ChatResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'الرسائل';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('message')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message')
            ->columns([
                Tables\Columns\TextColumn::make('sender.name')
                    ->label('المرسل'),
                Tables\Columns\TextColumn::make('message')
                    ->label('الرسالة')
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('الوقـت')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_read')
                    ->label('مقروءة')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('created_at', 'asc');
    }
}
