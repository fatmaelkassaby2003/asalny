<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserQuestionResource\Pages;
use App\Filament\Resources\UserQuestionResource\RelationManagers;
use App\Models\UserQuestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserQuestionResource extends Resource
{
    protected static ?string $model = UserQuestion::class;

    protected static ?string $navigationIcon = null;
    protected static ?string $navigationGroup = 'العمليات';
    protected static ?string $navigationLabel = 'الأسئلة';
    protected static ?string $modelLabel = 'سؤال';
    protected static ?string $pluralModelLabel = 'الأسئلة';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label('المستخدم')
                    ->required(),
                Forms\Components\Textarea::make('question')
                    ->label('السؤال')
                    ->required()
                    ->maxLength(65535),
                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->label('السعر')
                    ->prefix('$'),
                Forms\Components\Toggle::make('is_active')
                    ->label('نشط')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->state(fn ($record) => $record->user?->name)
                    ->searchable(),
                Tables\Columns\TextColumn::make('question')
                    ->label('السؤال')
                    ->state(fn ($record) => \Illuminate\Support\Str::limit($record->question, 50)),
                Tables\Columns\TextColumn::make('price')
                    ->label('السعر')
                    ->formatStateUsing(fn (string $state): string => '$' . number_format((float) $state, 2)),
                Tables\Columns\BooleanColumn::make('is_active')->label('نشط'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->state(fn ($record) => $record->created_at?->format('Y-m-d H:i:s')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->color('primary'),
                Tables\Actions\DeleteAction::make()->iconButton()->color('danger'),
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
            'index' => Pages\ListUserQuestions::route('/'),
            'create' => Pages\CreateUserQuestion::route('/create'),
            'edit' => Pages\EditUserQuestion::route('/{record}/edit'),
        ];
    }    
}
