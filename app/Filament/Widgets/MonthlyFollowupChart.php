<?php

namespace App\Filament\Widgets;

use App\Models\MonthlyFollowup;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class MonthlyFollowupChart extends ChartWidget
{
    protected static ?string $heading = 'Seguimientos Mensuales';
    protected static ?int $sort = 2;
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $data = [];
        $labels = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $labels[] = $date->translatedFormat('F Y');
            
            $data['completed'][] = MonthlyFollowup::where('year', $date->year)
                ->where('month', $date->month)
                ->where('status', 'completed')
                ->count();
                
            $data['pending'][] = MonthlyFollowup::where('year', $date->year)
                ->where('month', $date->month)
                ->where('status', 'pending')
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Completados',
                    'data' => $data['completed'],
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
                [
                    'label' => 'Pendientes',
                    'data' => $data['pending'],
                    'backgroundColor' => 'rgba(251, 146, 60, 0.2)',
                    'borderColor' => 'rgb(251, 146, 60)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}