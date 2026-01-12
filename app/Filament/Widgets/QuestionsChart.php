<?php

namespace App\Filament\Widgets;

use App\Models\UserQuestion;
use Filament\Widgets\ChartWidget;

class QuestionsChart extends ChartWidget
{
    protected static ?string $heading = 'حركة الأسئلة خلال الشهر';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $data = UserQuestion::selectRaw('DATE(created_at) as date, count(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Fill missing days with 0
        $chartData = [];
        $labels = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartData[] = $data[$date] ?? 0;
            $labels[] = now()->subDays($i)->format('d');
        }

        return [
            'datasets' => [
                [
                    'label' => 'الأسئلة',
                    'data' => $chartData,
                    'fill' => true,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'borderColor' => '#10b981',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
