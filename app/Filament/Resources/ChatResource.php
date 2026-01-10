<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChatResource\Pages;
use App\Filament\Resources\ChatResource\RelationManagers;
use App\Models\Chat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChatResource extends Resource
{
    protected static ?string $model = Chat::class;

    protected static ?string $navigationIcon = null;
    protected static ?string $navigationGroup = 'الدعم';
    protected static ?string $navigationLabel = 'المحادثات';
    protected static ?string $modelLabel = 'محادثة';
    protected static ?string $pluralModelLabel = 'المحادثات';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('order_id')
                    ->relationship('order', 'id')
                    ->label('رقم الطلب')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('رقم المحادثة')
                    ->state(fn ($record) => (string) $record->id)
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.id')
                    ->label('رقم الطلب')
                    ->state(fn ($record) => (string) $record->order?->id)
                    ->searchable(),
                Tables\Columns\TextColumn::make('order.asker.name')
                    ->label('السائل')
                    ->state(fn ($record) => $record->order?->asker?->name)
                    ->searchable(),
                Tables\Columns\TextColumn::make('order.answerer.name')
                    ->label('المجيب')
                    ->state(fn ($record) => $record->order?->answerer?->name)
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->state(fn ($record) => $record->created_at?->format('Y-m-d H:i:s')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->color('primary'),
            ])
            ->actionsColumnLabel('الاجراءات')
            ->actionsAlignment('left')
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChats::route('/'),
            'create' => Pages\CreateChat::route('/create'),
            'edit' => Pages\EditChat::route('/{record}/edit'),
        ];
    }    
}
