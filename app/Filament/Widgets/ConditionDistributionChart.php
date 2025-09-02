<?php

// ConditionDistributionChart.php - Con Permisos
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

    // ✅ Control de acceso al widget
    public static function canView(): bool
    {
        return auth()->user()->can('view_dashboard') && 
               auth()->user()->can('view_analytics');
    }

    protected function getData(): array
    {
        // ✅ Verificar permisos antes de mostrar datos
        if (!auth()->user()->can('view_patients')) {
            return [
                'datasets' => [
                    [
                        'label' => 'Sin Acceso',
                        'data' => [1],
                        'backgroundColor' => ['rgba(239, 68, 68, 0.5)'],
                    ],
                ],
                'labels' => ['Acceso Restringido'],
            ];
        }

        $mentalDisordersQuery = MentalDisorder::where('status', 'active');
        $suicideAttemptsQuery = SuicideAttempt::where('status', 'active');
        $substanceConsumptionsQuery = SubstanceConsumption::whereIn('status', ['active', 'in_treatment']);

        // ✅ Aplicar filtros si no puede ver todos los pacientes
        if (!auth()->user()->can('view_any_patients')) {
            $mentalDisordersQuery->whereHas('patient', function ($q) {
                $q->where('assigned_to', auth()->id());
            });
            $suicideAttemptsQuery->whereHas('patient', function ($q) {
                $q->where('assigned_to', auth()->id());
            });
            $substanceConsumptionsQuery->whereHas('patient', function ($q) {
                $q->where('assigned_to', auth()->id());
            });
        }

        return [
            'datasets' => [
                [
                    'label' => 'Casos Activos',
                    'data' => [
                        $mentalDisordersQuery->count(),
                        $suicideAttemptsQuery->count(),
                        $substanceConsumptionsQuery->count(),
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