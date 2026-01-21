<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DisputeResource\Pages;
use App\Models\Order;
use App\Helpers\NotificationHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class DisputeResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    
    protected static ?string $navigationLabel = 'الاعتراضات';
    
    protected static ?string $modelLabel = 'اعتراض';
    
    protected static ?string $pluralModelLabel = 'الاعتراضات';
    
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where(function($query) {
                $query->where('dispute_count', '>', 0)
                      ->orWhere('status', 'under_review');
            })
            ->with(['asker', 'answerer', 'question'])
            ->latest('disputed_at');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('تفاصيل الطلب')
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->label('رقم الطلب')
                            ->disabled(),
                        Forms\Components\TextInput::make('question.title')
                            ->label('عنوان السؤال')
                            ->disabled(),
                        Forms\Components\TextInput::make('asker.name')
                            ->label('السائل')
                            ->disabled(),
                        Forms\Components\TextInput::make('answerer.name')
                            ->label('المجيب')
                            ->disabled(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('تفاصيل الاعتراض')
                    ->schema([
                        Forms\Components\TextInput::make('dispute_count')
                            ->label('عدد الاعتراضات')
                            ->disabled(),
                        Forms\Components\Textarea::make('dispute_reason')
                            ->label('سبب الاعتراض')
                            ->disabled()
                            ->rows(3),
                        Forms\Components\Textarea::make('admin_response')
                            ->label('رد الإدارة')
                            ->rows(4)
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('رقم الطلب')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('question.title')
                    ->label('السؤال')
                    ->limit(30)
                    ->searchable(),
                Tables\Columns\TextColumn::make('asker.name')
                    ->label('السائل')
                    ->searchable(),
                Tables\Columns\TextColumn::make('answerer.name')
                    ->label('المجيب')
                    ->searchable(),
                Tables\Columns\TextColumn::make('dispute_count')
                    ->label('الاعتراضات')
                    ->badge()
                    ->color(fn ($state) => $state >= 2 ? 'danger' : 'warning'),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'disputed' => 'warning',
                        'under_review' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('admin_response')
                    ->label('تم الرد')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('disputed_at')
                    ->label('تاريخ الاعتراض')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'disputed' => 'معترض عليه',
                        'under_review' => 'قيد المراجعة',
                    ]),
                Tables\Filters\Filter::make('not_responded')
                    ->label('لم يتم الرد')
                    ->query(fn ($query) => $query->whereNull('admin_response')),
            ])
            ->actions([
                Tables\Actions\Action::make('respond')
                    ->label('إضافة رد')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->visible(fn (Order $record) => !$record->admin_response)
                    ->form([
                        Forms\Components\Textarea::make('admin_response')
                            ->label('رد الإدارة')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (Order $record, array $data) {
                        $record->update([
                            'admin_response' => $data['admin_response'],
                            'admin_responded_at' => now(),
                        ]);
                        
                        // Send notification to asker
                        NotificationHelper::notifyAdminResponse($record, $record->asker);
                        
                        Notification::make()
                            ->title('تم إرسال الرد')
                            ->success()
                            ->body('تم إرسال الرد للسائل بنجاح')
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('disputed_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDisputes::route('/'),
        ];
    }
}
