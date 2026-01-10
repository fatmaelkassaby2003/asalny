<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class StatsOverview extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('إجمالي المستخدمين', \App\Models\User::count())
                ->description('جميع المستخدمين المسجلين')
                ->descriptionIcon('heroicon-s-users')
                ->color('primary'),
            Card::make('إجمالي الطلبات', \App\Models\Order::count())
                ->description('جميع الطلبات التي تم إنشاؤها')
                ->descriptionIcon('heroicon-s-shopping-bag')
                ->color('success'),
            Card::make('إجمالي الأسئلة', \App\Models\UserQuestion::count())
                ->description('جميع الأسئلة المطروحة')
                ->descriptionIcon('heroicon-s-question-mark-circle')
                ->color('primary'),
        ];
    }
}
