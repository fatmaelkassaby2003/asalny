<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionOfferResource\Pages;
use App\Filament\Resources\QuestionOfferResource\RelationManagers;
use App\Models\QuestionOffer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuestionOfferResource extends Resource
{
    protected static ?string $model = QuestionOffer::class;

    protected static ?string $navigationIcon = null;
    protected static ?string $navigationGroup = 'العمليات';
    protected static ?string $navigationLabel = 'عروض الأسعار';
    protected static ?string $modelLabel = 'عرض';
    protected static ?string $pluralModelLabel = 'عروض الأسعار';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('answerer_id')
                    ->relationship('answerer', 'name')
                    ->label('المستخدم')
                    ->required(),
                Forms\Components\Select::make('question_id')
                    ->relationship('question', 'question')
                    ->label('السؤال')
                    ->required(),
                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->prefix('$')
                    ->label('السعر')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'معلق',
                        'accepted' => 'مقبول',
                        'rejected' => 'مرفوض',
                    ])
                    ->label('الحالة')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('answerer.name')
                    ->label('المستخدم')
                    ->state(fn ($record) => $record->answerer?->name)
                    ->searchable(),
                Tables\Columns\TextColumn::make('question.question')
                    ->label('السؤال')
                    ->state(fn ($record) => \Illuminate\Support\Str::limit($record->question?->question, 50)),
                Tables\Columns\TextColumn::make('price')
                    ->state(fn ($record) => \Illuminate\Support\Str::limit($record->question?->question, 50)),
                Tables\Columns\TextColumn::make('price')
                    ->label('السعر')
                    ->state(fn ($record) => '$' . number_format((float) $record->price, 2)),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'معلق',
                        'accepted' => 'مقبول',
                        'rejected' => 'مرفوض',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'info',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ العرض')
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
            'index' => Pages\ListQuestionOffers::route('/'),
            'create' => Pages\CreateQuestionOffer::route('/create'),
            'edit' => Pages\EditQuestionOffer::route('/{record}/edit'),
        ];
    }    
}
