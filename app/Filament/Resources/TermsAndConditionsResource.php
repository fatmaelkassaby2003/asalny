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
                Forms\Components\Section::make('المحتوى بالعربي')
                    ->icon('heroicon-o-language')
                    ->description('أدخل المحتوى باللغة العربية')
                    ->schema([
                        Forms\Components\TextInput::make('title_ar')
                            ->label('العنوان')
                            ->placeholder('مثال: الشروط والأحكام')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('content_ar')
                            ->label('المحتوى')
                            ->placeholder('اكتب المحتوى هنا...')
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                                'link',
                                'redo',
                                'undo',
                            ]),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                Forms\Components\Section::make('Content in English')
                    ->icon('heroicon-o-language')
                    ->description('Enter content in English')
                    ->schema([
                        Forms\Components\TextInput::make('title_en')
                            ->label('Title')
                            ->placeholder('Example: Terms and Conditions')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('content_en')
                            ->label('Content')
                            ->placeholder('Write content here...')
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                                'link',
                                'redo',
                                'undo',
                            ]),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                Forms\Components\Section::make('الإعدادات')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('مفعلة')
                            ->helperText('تفعيل أو تعطيل ظهور الصفحة في التطبيق')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title_ar')
                    ->label('العنوان')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('مفعلة')
                    ->onColor('success')
                    ->offColor('danger'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
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
