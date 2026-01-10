<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = null;
    protected static ?string $navigationGroup = 'العمليات';
    protected static ?string $navigationLabel = 'الطلبات';
    protected static ?string $modelLabel = 'طلب';
    protected static ?string $pluralModelLabel = 'الطلبات';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('asker_id')
                    ->relationship('asker', 'name')
                    ->label('السائل')
                    ->required(),
                Forms\Components\Select::make('answerer_id')
                    ->relationship('answerer', 'name')
                    ->label('المجيب')
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
                        'answered' => 'تمت الإجابة',
                        'cancelled' => 'ملغي',
                        'expired' => 'منتهي',
                        'completed' => 'مكتمل',
                    ])
                    ->label('الحالة')
                    ->required(),
                Forms\Components\DateTimePicker::make('expires_at')->label('تاريخ الانتهاء'),
                Forms\Components\DateTimePicker::make('answered_at')->label('تاريخ الإجابة'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('رقم الطلب')
                    ->state(fn ($record) => (string) $record->id)
                    ->sortable(),
                Tables\Columns\TextColumn::make('asker.name')
                    ->label('السائل')
                    ->state(fn ($record) => $record->asker?->name)
                    ->searchable(),
                Tables\Columns\TextColumn::make('answerer.name')
                    ->label('المجيب')
                    ->state(fn ($record) => $record->answerer?->name)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'معلق',
                        'answered' => 'تمت الإجابة',
                        'cancelled' => 'ملغي',
                        'expired' => 'منتهي',
                        'completed' => 'مكتمل',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'info', // Changed from primary to info for V3 standard blue
                        'answered' => 'success',
                        'cancelled' => 'danger',
                        'expired' => 'gray', // Secondary mapped to gray
                        'completed' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('price')
                    ->label('السعر')
                    ->state(fn ($record) => '$' . number_format((float) $record->price, 2)),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->state(fn ($record) => $record->created_at?->format('Y-m-d H:i:s')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'معلق',
                        'answered' => 'تمت الإجابة',
                        'cancelled' => 'ملغي',
                        'expired' => 'منتهي',
                    ])
                    ->label('الحالة'),
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }    
}
