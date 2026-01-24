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
                // العناوين (صف واحد - عمودين)
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('title_ar')
                            ->label('العنوان بالعربي')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('title_en')
                            ->label('العنوان بالإنجليزي')
                            ->required()
                            ->maxLength(255),
                    ]),

                // // فاصل بين العناوين والمحتوى
                // Forms\Components\Placeholder::make('spacer_1')
                //     ->label('')
                //     ->content('')
                //     ->extraAttributes(['style' => 'height: 0px;'])
                //     ->columnSpanFull(),

                // المحتوى (صف واحد - عمودين)
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\RichEditor::make('content_ar')
                            ->label('المحتوى بالعربي')
                            ->required()
                            ->extraAttributes(['style' => 'max-height: 200px; overflow-y: auto;']),

                        Forms\Components\RichEditor::make('content_en')
                            ->label('المحتوى بالإنجليزي')
                            ->required()
                            ->extraAttributes(['style' => 'max-height: 200px; overflow-y: auto;']),
                    ]),

                // فاصل كبير بين المحتوى وبيانات التواصل
                Forms\Components\Placeholder::make('spacer_2')
                    ->label('')
                    ->content('')
                    ->extraAttributes(['style' => 'height: 50px;'])
                    ->columnSpanFull(),

                // بيانات التواصل (كل حقل في سطر منفصل)
                Forms\Components\TextInput::make('app_version')
                    ->label('إصدار التطبيق')
                    ->maxLength(50)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('contact_email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('contact_phone')
                    ->label('رقم التواصل')
                    ->tel()
                    ->maxLength(50)
                    ->columnSpanFull(),
                // // فاصل بين بيانات التواصل والتفعيل
                // Forms\Components\Placeholder::make('spacer_3')
                //     ->label('')
                //     ->content('')
                //     ->extraAttributes(['style' => 'height: 0px;'])
                //     ->columnSpanFull(),

                // زر التفعيل
                Forms\Components\Toggle::make('is_active')
                    ->label('مفعلة')
                    ->default(true),
                    
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
