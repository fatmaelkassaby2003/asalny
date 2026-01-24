<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminNotificationResource\Pages;
use App\Models\User;
use App\Models\Notification;
use App\Helpers\NotificationHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification as FilamentNotification;

class AdminNotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';
    
    protected static ?string $navigationLabel = 'إرسال إشعارات';
    
    protected static ?string $modelLabel = 'إشعار';
    
    protected static ?string $pluralModelLabel = 'الإشعارات';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // صف الإرسال والمستخدم
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('send_to')
                            ->label('إرسال إلى')
                            ->options([
                                'all' => 'كل المستخدمين',
                                'specific' => 'مستخدم محدد',
                            ])
                            ->required()
                            ->live()
                            ->default('specific'),
                            
                        Forms\Components\Select::make('user_id')
                            ->label('اختر المستخدم')
                            ->options(User::pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn ($get) => $get('send_to') === 'specific')
                            ->required(fn ($get) => $get('send_to') === 'specific')
                            ->placeholder('ابحث عن المستخدم...'),
                    ]),

                // فاصل
                Forms\Components\Placeholder::make('spacer_1')
                    ->label('')
                    ->content('')
                    ->extraAttributes(['style' => 'height: 20px;'])
                    ->columnSpanFull(),

                // صف العنوان والنوع
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان الإشعار')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('مثال: تحديث جديد متاح'),
                            
                        Forms\Components\Select::make('type')
                            ->label('نوع الإشعار')
                            ->options([
                                'announcement' => '📢 إعلان',
                                'info' => 'ℹ️ معلومة',
                                'warning' => '⚠️ تحذير',
                                'promo' => '🎁 عرض',
                            ])
                            ->required()
                            ->default('announcement'),
                    ]),

                // فاصل
                Forms\Components\Placeholder::make('spacer_2')
                    ->label('')
                    ->content('')
                    ->extraAttributes(['style' => 'height: 20px;'])
                    ->columnSpanFull(),

                // محتوى الإشعار
                Forms\Components\Textarea::make('body')
                    ->label('نص الإشعار')
                    ->required()
                    ->rows(5)
                    ->placeholder('اكتب محتوى الإشعار هنا...')
                    ->maxLength(500)
                    ->columnSpanFull(),

                // فاصل قبل الأزرار
                Forms\Components\Placeholder::make('spacer_final')
                    ->label('')
                    ->content('')
                    ->extraAttributes(['style' => 'height: 50px;'])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('body')
                    ->label('المحتوى')
                    ->limit(50),
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'announcement' => 'primary',
                        'info' => 'info',
                        'warning' => 'warning',
                        'promo' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_read')
                    ->label('مقروءة')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        'announcement' => 'إعلان',
                        'info' => 'معلومة',
                        'warning' => 'تحذير',
                        'promo' => 'عرض',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض')
                    ->iconButton()
                    ->color('primary'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminNotifications::route('/'),
            'create' => Pages\CreateAdminNotification::route('/create'),
        ];
    }
    
    public static function canEdit($record): bool
    {
        return false; // لا يمكن تعديل الإشعارات
    }
}
