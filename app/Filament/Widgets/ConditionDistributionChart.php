<?php

namespace App\Filament\Widgets;

use App\Models\MentalDisorder;
use App\Models\SuicideAttempt;
use App\Models\SubstanceConsumption;
use Filament\Widgets\ChartWidget;

class ConditionDistributionChart extends ChartWidget
{
    protected static ?string $heading = 'Distribución de Casos Activos';
    protected static ?int $sort = 3;
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Casos Activos',
                    'data' => [
                        MentalDisorder::where('status', 'active')->count(),
                        SuicideAttempt::where('status', 'active')->count(),
                        SubstanceConsumption::whereIn('status', ['active', 'in_treatment'])->count(),
                    ],
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.5)',
                        'rgba(239, 68, 68, 0.5)',
                        'rgba(251, 146, 60, 0.5)',
                    ],
                ],
            ],
            'labels' => ['Trastornos Mentales', 'Intentos de Suicidio', 'Consumo SPA'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}