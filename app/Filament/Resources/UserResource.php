<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = null;
    protected static ?string $navigationGroup = 'الإدارة';

    protected static ?string $navigationLabel = 'المستخدمين';

    protected static ?string $modelLabel = 'مستخدم';

    protected static ?string $pluralModelLabel = 'المستخدمين';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('الاسم الكامل')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('phone')
                    ->label('رقم الهاتف')
                    ->tel()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(20),

                Forms\Components\TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\Select::make('gender')
                    ->label('الجنس')
                    ->options([
                        'male' => 'ذكر',
                        'female' => 'أنثى',
                    ])
                    ->required(),

                Forms\Components\Toggle::make('is_driver')
                    ->label('مجيب')
                    ->helperText('فعّل إذا كان المستخدم مجيب، أو اتركه إذا كان سائق')
                    ->default(false),

                Forms\Components\Toggle::make('is_active')
                    ->label('الحساب مفعل')
                    ->helperText('يمكنك تفعيل أو تعطيل الحساب')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->state(fn ($record) => (string) $record->id)
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('الهاتف')
                    ->state(fn ($record) => (string) $record->phone)
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('البريد')
                    ->searchable(),

                Tables\Columns\TextColumn::make('gender')
                    ->label('الجنس')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'male' => 'ذكر',
                        'female' => 'أنثى',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'male' => 'info',
                        'female' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\ToggleColumn::make('is_driver')
                    ->label('مجيب')
                    ->onColor('primary')
                    ->offColor('warning')
                    ->onIcon('heroicon-s-check')
                    ->offIcon('heroicon-s-x-mark'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('نشط')
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-s-check')
                    ->offIcon('heroicon-s-x-mark'),

                Tables\Columns\TextColumn::make('registered_at')
                    ->label('تاريخ التسجيل')
                    ->state(fn ($record) => $record->created_at?->format('Y-m-d H:i')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gender')
                    ->label('الجنس')
                    ->options([
                        'male' => 'ذكر',
                        'female' => 'أنثى',
                    ]),

                Tables\Filters\TernaryFilter::make('is_driver')
                    ->label('النوع')
                    ->placeholder('الكل')
                    ->trueLabel('مجيب')
                    ->falseLabel('سائق'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('مفعل')
                    ->falseLabel('معطل'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض')
                    ->iconButton()
                    ->color('primary'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->iconButton()
                    ->color('primary'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->iconButton()
                    ->color('danger')
                    ->requiresConfirmation(),
            ])
            ->actionsColumnLabel('الاجراءات')
            ->actionsAlignment('left')
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('حذف المحدد'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
