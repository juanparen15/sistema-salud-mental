<?php

// ================================
// DASHBOARD PERSONALIZADO FINAL
// ================================

// app/Filament/Pages/Dashboard.php
namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->can('view_dashboard');
    }

    public function getTitle(): string
    {
        $user = auth()->user();
        $role = $user->roles->first()?->name ?? 'usuario';
        
        return match ($role) {
            'super_admin' => '🔧 Panel de Super Administrador',
            'admin' => '👨‍💼 Panel de Administración',
            'coordinator' => '👨‍🏫 Panel de Coordinación',
            'psychologist' => '👨‍⚕️ Panel de Psicología',
            'social_worker' => '👨‍💼 Panel de Trabajo Social',
            'assistant' => '👨‍💻 Panel de Asistente',
            default => '🏠 Panel de Control',
        };
    }

    public function getSubheading(): ?string
    {
        $user = auth()->user();
        
        // Obtener estadísticas contextuales según permisos
        $stats = [];
        
        if ($user->can('view_patients')) {
            $totalPatients = \App\Models\Patient::count();
            $stats[] = "{$totalPatients} pacientes";
        }
        
        if ($user->can('view_followups')) {
            $pendingFollowups = \App\Models\MonthlyFollowup::where('status', 'pending');
            
            // Filtrar según permisos
            if (!$user->can('view_all_followups')) {
                if ($user->can('view_any_followups')) {
                    $pendingFollowups->whereHas('followupable.patient', function ($q) use ($user) {
                        $q->where('assigned_to', $user->id);
                    });
                } else {
                    $pendingFollowups->where('performed_by', $user->id);
                }
            }
            
            $count = $pendingFollowups->count();
            $stats[] = "{$count} seguimientos pendientes";
        }

        $welcomeMessage = "Bienvenido, {$user->name}";
        
        if (!empty($stats)) {
            $welcomeMessage .= " | " . implode(' | ', $stats);
        }

        return $welcomeMessage;
    }

    public function getWidgets(): array
    {
        $widgets = [];
        
        if (auth()->user()->can('view_analytics')) {
            $widgets[] = \App\Filament\Widgets\StatsOverviewWidget::class;
        }
        
        if (auth()->user()->can('view_followups') || auth()->user()->can('view_any_followups')) {
            $widgets[] = \App\Filament\Widgets\MonthlyFollowupChart::class;
        }
        
        if (auth()->user()->can('view_analytics')) {
            $widgets[] = \App\Filament\Widgets\ConditionDistributionChart::class;
        }

        return $widgets;
    }

    public function getColumns(): int | string | array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}