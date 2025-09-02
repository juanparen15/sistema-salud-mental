<?php

// StatsOverviewWidget.php - Con Permisos
namespace App\Filament\Widgets;

use App\Models\Patient;
use App\Models\MentalDisorder;
use App\Models\SuicideAttempt;
use App\Models\SubstanceConsumption;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    // ✅ Control de acceso al widget
    public static function canView(): bool
    {
        return auth()->user()->can('view_dashboard') && 
               auth()->user()->can('view_analytics');
    }

    protected function getStats(): array
    {
        // ✅ Solo mostrar estadísticas si tiene permisos
        if (!auth()->user()->can('view_patients')) {
            return [
                Stat::make('Acceso Restringido', '---')
                    ->description('No tienes permisos para ver estas estadísticas')
                    ->color('danger'),
            ];
        }

        $totalPatients = Patient::count();
        $mentalDisorders = MentalDisorder::where('status', 'active')->count();
        $suicideAttempts = SuicideAttempt::where('status', 'active')->count();
        $substanceConsumptions = SubstanceConsumption::whereIn('status', ['active', 'in_treatment'])->count();
        
        // Cálculos de tendencia (últimos 30 días)
        $newPatientsThisMonth = Patient::where('created_at', '>=', now()->subDays(30))->count();
        $newPatientsLastMonth = Patient::whereBetween('created_at', [
            now()->subDays(60),
            now()->subDays(30)
        ])->count();
        
        $patientTrend = $newPatientsLastMonth > 0 
            ? round((($newPatientsThisMonth - $newPatientsLastMonth) / $newPatientsLastMonth) * 100, 1)
            : 0;

        return [
            Stat::make('Total Pacientes', $totalPatients)
                ->description($patientTrend >= 0 ? "+{$patientTrend}% este mes" : "{$patientTrend}% este mes")
                ->descriptionIcon($patientTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($patientTrend >= 0 ? 'success' : 'danger')
                ->chart($this->getPatientTrendData()),
                
            Stat::make('Trastornos Mentales Activos', $mentalDisorders)
                ->description('Casos en seguimiento')
                ->descriptionIcon('heroicon-m-heart')
                ->color('primary'),
                
            Stat::make('Intentos de Suicidio', $suicideAttempts)
                ->description('Casos activos')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
                
            Stat::make('Consumo SPA', $substanceConsumptions)
                ->description('En tratamiento')
                ->descriptionIcon('heroicon-m-beaker')
                ->color('warning'),
        ];
    }

    protected function getPatientTrendData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $count = Patient::whereDate('created_at', now()->subDays($i))->count();
            $data[] = $count;
        }
        return $data;
    }
}