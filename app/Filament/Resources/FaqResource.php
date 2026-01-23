<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Models\Faq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationGroup = 'الصفحات الثابتة';
    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'الأسئلة الشائعة';
    protected static ?string $modelLabel = 'سؤال';
    protected static ?string $pluralModelLabel = 'الأسئلة الشائعة';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('السؤال والإجابة بالعربي')
                    ->icon('heroicon-o-language')
                    ->description('أدخل السؤال والإجابة باللغة العربية')
                    ->schema([
                        Forms\Components\TextInput::make('question_ar')
                            ->label('السؤال')
                            ->placeholder('مثال: كيف يمكنني التسجيل؟')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('answer_ar')
                            ->label('الإجابة')
                            ->placeholder('اكتب الإجابة هنا...')
                            ->required()
                            ->rows(6)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                Forms\Components\Section::make('Question & Answer in English')
                    ->icon('heroicon-o-language')
                    ->description('Enter question and answer in English')
                    ->schema([
                        Forms\Components\TextInput::make('question_en')
                            ->label('Question')
                            ->placeholder('Example: How can I register?')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('answer_en')
                            ->label('Answer')
                            ->placeholder('Write answer here...')
                            ->required()
                            ->rows(6)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                Forms\Components\Section::make('الإعدادات')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('مفعل')
                            ->helperText('تفعيل أو تعطيل ظهور السؤال في التطبيق')
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
                Tables\Columns\TextColumn::make('question_ar')
                    ->label('السؤال')
                    ->searchable()
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('answer_ar')
                    ->label('الإجابة')
                    ->limit(40)
                    ->wrap(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('مفعل')
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
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('حذف المحدد'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFaqs::route('/'),
            'create' => Pages\CreateFaq::route('/create'),
            'edit' => Pages\EditFaq::route('/{record}/edit'),
        ];
    }
}
