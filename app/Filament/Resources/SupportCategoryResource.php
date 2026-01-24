<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportCategoryResource\Pages;
use App\Models\SupportCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupportCategoryResource extends Resource
{
    protected static ?string $model = SupportCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'الدعم الفني';
    protected static ?string $navigationLabel = 'أنواع المشاكل';
    protected static ?string $modelLabel = 'نوع مشكلة';
    protected static ?string $pluralModelLabel = 'أنواع المشاكل';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // صف الأسماء
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('name_ar')
                            ->label('الاسم بالعربي')
                            ->required(),

                        Forms\Components\TextInput::make('name_en')
                            ->label('الاسم بالإنجليزي')
                            ->nullable()
                            ->maxLength(255),
                    ]),

                // الأيقونة
                Forms\Components\FileUpload::make('icon')
                    ->label('الأيقونة')
                    ->image()
                    ->directory('support-icons')
                    ->columnSpanFull(),

                // زر التفعيل
                Forms\Components\Toggle::make('is_active')
                    ->label('مفعل')
                    ->default(true),
                    
                    
                // مسافة ضخمة قبل أزرار الحفظ
                Forms\Components\Placeholder::make('form_actions_spacer')
                    ->label(''),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name_ar')->label('الاسم بالعربي')->searchable(),
                Tables\Columns\TextColumn::make('name_en')->label('الاسم بالإنجليزية')->searchable(),
                Tables\Columns\ImageColumn::make('icon')->label('الأيقونة'),
                Tables\Columns\ToggleColumn::make('is_active')->label('مفعل'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportCategories::route('/'),
            'create' => Pages\CreateSupportCategory::route('/create'),
            'edit' => Pages\EditSupportCategory::route('/{record}/edit'),
        ];
    }
}
