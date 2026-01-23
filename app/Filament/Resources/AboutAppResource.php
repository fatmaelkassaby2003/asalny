<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AboutAppResource\Pages;
use App\Models\AboutApp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class AboutAppResource extends Resource
{
    protected static ?string $model = AboutApp::class;

    protected static ?string $navigationIcon = 'heroicon-o-information-circle';
    protected static ?string $navigationGroup = 'الصفحات الثابتة';
    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'عن التطبيق';
    protected static ?string $modelLabel = 'عن التطبيق';
    protected static ?string $pluralModelLabel = 'عن التطبيق';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title_ar')
                    ->label('العنوان بالعربي')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('title_en')
                    ->label('العنوان بالإنجليزي')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\RichEditor::make('content_ar')
                    ->label('المحتوى بالعربي')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\RichEditor::make('content_en')
                    ->label('المحتوى بالإنجليزي')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('app_version')
                    ->label('إصدار التطبيق')
                    ->maxLength(50),

                Forms\Components\TextInput::make('contact_email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->maxLength(255),

                Forms\Components\TextInput::make('contact_phone')
                    ->label('رقم التواصل')
                    ->tel()
                    ->maxLength(50),

                Forms\Components\Toggle::make('is_active')
                    ->label('مفعلة')
                    ->default(true)
                    ->inline(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title_ar')
                    ->label('العنوان')
                    ->searchable(),
                Tables\Columns\TextColumn::make('app_version')
                    ->label('الإصدار')
                    ->badge()
                    ->color('info'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('مفعلة')
                    ->onColor('success')
                    ->offColor('danger'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime('Y-m-d H:i'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض')
                    ->iconButton()
                    ->color('primary'),
                Tables\Actions\EditAction::make()->iconButton()->color('primary'),
                Tables\Actions\DeleteAction::make()->iconButton()->color('danger'),
            ])
            ->actionsColumnLabel('الاجراءات')
            ->actionsAlignment('left')
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAboutApps::route('/'),
            'create' => Pages\CreateAboutApp::route('/create'),
            'edit' => Pages\EditAboutApp::route('/{record}/edit'),
        ];
    }
}
