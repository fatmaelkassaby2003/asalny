<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportTicketResource\Pages;
use App\Models\SupportTicket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'الدعم الفني';
    protected static ?string $navigationLabel = 'البلاغات';
    protected static ?string $modelLabel = 'بلاغ';
    protected static ?string $pluralModelLabel = 'البلاغات';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('تفاصيل البلاغ')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('المستخدم')
                            ->disabled(),
                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name_ar')
                            ->label('نوع المشكلة')
                            ->disabled(),
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان المشكلة')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->label('وصف المشكلة')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('image')
                            ->label('صورة مرفقة')
                            ->image()
                            ->disabled()
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record && $record->image),
                    ])->columns(2),

                Forms\Components\Section::make('رد الإدارة')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'pending' => 'قيد الانتظار',
                                'in_progress' => 'جاري العمل',
                                'resolved' => 'تم الحل',
                                'closed' => 'مغلق',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('admin_response')
                            ->label('الرد')
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('responded_at')
                            ->label('وقت الرد')
                            ->native(false)
                            ->default(now()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('المستخدم')->searchable(),
                Tables\Columns\TextColumn::make('category.name_ar')->label('النوع')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('العنوان')->searchable()->limit(30),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'warning',
                        'resolved' => 'success',
                        'closed' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'قيد الانتظار',
                        'in_progress' => 'جاري العمل',
                        'resolved' => 'تم الحل',
                        'closed' => 'مغلق',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ الإنشاء')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'in_progress' => 'جاري العمل',
                        'resolved' => 'تم الحل',
                        'closed' => 'مغلق',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name_ar')
                    ->label('النوع'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportTickets::route('/'),
            // 'create' => Pages\CreateSupportTicket::route('/create'), // الغاء الانشاء من الادمن
            'edit' => Pages\EditSupportTicket::route('/{record}/edit'),
            'view' => Pages\ViewSupportTicket::route('/{record}'),
        ];
    }
}
