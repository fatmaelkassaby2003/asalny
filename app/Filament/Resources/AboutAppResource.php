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
                Forms\Components\Section::make('المحتوى بالعربي')
                    ->icon('heroicon-o-language')
                    ->description('أدخل المحتوى باللغة العربية')
                    ->schema([
                        Forms\Components\TextInput::make('title_ar')
                            ->label('العنوان')
                            ->placeholder('مثال: عن التطبيق')
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
                            ->placeholder('Example: About the App')
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

                Forms\Components\Section::make('معلومات التطبيق')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->description('معلومات إضافية عن التطبيق')
                    ->schema([
                        Forms\Components\TextInput::make('app_version')
                            ->label('إصدار التطبيق')
                            ->placeholder('مثال: 1.0.0')
                            ->maxLength(50)
                            ->prefixIcon('heroicon-o-hashtag'),

                        Forms\Components\TextInput::make('contact_email')
                            ->label('البريد الإلكتروني')
                            ->placeholder('example@domain.com')
                            ->email()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-envelope'),

                        Forms\Components\TextInput::make('contact_phone')
                            ->label('رقم التواصل')
                            ->placeholder('+966 5XX XXX XXX')
                            ->tel()
                            ->maxLength(50)
                            ->prefixIcon('heroicon-o-phone'),
                    ])
                    ->columns(3)
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

                Tables\Columns\TextColumn::make('app_version')
                    ->label('الإصدار')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('contact_email')
                    ->label('البريد')
                    ->icon('heroicon-o-envelope'),

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
            'index' => Pages\ListAboutApps::route('/'),
            'create' => Pages\CreateAboutApp::route('/create'),
            'edit' => Pages\EditAboutApp::route('/{record}/edit'),
        ];
    }
}
