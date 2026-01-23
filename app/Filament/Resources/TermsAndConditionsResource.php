<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TermsAndConditionsResource\Pages;
use App\Models\TermsAndConditions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class TermsAndConditionsResource extends Resource
{
    protected static ?string $model = TermsAndConditions::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'الصفحات الثابتة';
    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'الشروط والأحكام';
    protected static ?string $modelLabel = 'الشروط والأحكام';
    protected static ?string $pluralModelLabel = 'الشروط والأحكام';

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
            'index' => Pages\ListTermsAndConditions::route('/'),
            'create' => Pages\CreateTermsAndConditions::route('/create'),
            'edit' => Pages\EditTermsAndConditions::route('/{record}/edit'),
        ];
    }
}
