<?php

namespace App\Filament\Widgets;

use Filament\Widgets\LineChartWidget;

class OrdersChart extends LineChartWidget
{
    protected static ?string $heading = 'حركة الطلبات خلال الأسبوع';

    protected function getData(): array
    {
        $data = \App\Models\Order::selectRaw('DATE(created_at) as date, count(*) as count')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Fill missing days with 0
        $chartData = [];
        $labels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartData[] = $data[$date] ?? 0;
            $labels[] = now()->subDays($i)->format('M d');
        }

        return [
            'datasets' => [
                [
                    'label' => 'الطلبات',
                    'data' => $chartData,
                    'fill' => 'start',
                    'borderColor' => '#3b82f6',
                ],
            ],
            'labels' => $labels,
        ];
    }
}
