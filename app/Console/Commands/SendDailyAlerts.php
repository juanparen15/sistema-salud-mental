<?php

// ================================
// COMANDO ARTISAN PARA NOTIFICACIONES
// ================================

namespace App\Console\Commands;

use App\Models\MonthlyFollowup;
use App\Models\SuicideAttempt;
use App\Models\User;
use Illuminate\Console\Command;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class SendDailyAlerts extends Command
{
    protected $signature = 'mental-health:send-daily-alerts';
    protected $description = 'Envía alertas diarias a los usuarios según sus permisos y responsabilidades';

    public function handle()
    {
        $this->info('Enviando alertas diarias del sistema de salud mental...');

        $users = User::with('roles')->get();

        foreach ($users as $user) {
            $this->sendAlertsForUser($user);
        }

        $this->info('Alertas enviadas correctamente.');
        return 0;
    }

    private function sendAlertsForUser(User $user)
    {
        $alerts = [];

        // ✅ Solo enviar alertas si el usuario tiene permisos básicos
        if (!$user->can('view_dashboard')) {
            return;
        }

        // Seguimientos vencidos personales
        if ($user->can('view_followups')) {
            $overdueCount = MonthlyFollowup::where('performed_by', $user->id)
                ->where('next_followup', '<', now())
                ->whereNotNull('next_followup')
                ->where('status', 'pending')
                ->count();

            if ($overdueCount > 0) {
                Notification::make()
                    ->title('Seguimientos Vencidos')
                    ->body("Tienes {$overdueCount} seguimientos vencidos que requieren atención.")
                    ->warning()
                    ->actions([
                        Action::make('ver')
                            ->button()
                            ->url(route('filament.admin.resources.monthly-followups.index'))
                            ->label('Ver Seguimientos'),
                    ])
                    ->sendToDatabase($user);
            }
        }

        // Alertas para coordinadores y administradores
        if ($user->can('view_all_followups')) {
            $criticalCases = SuicideAttempt::where('status', 'active')
                ->whereDoesntHave('followups', function ($q) {
                    $q->where('followup_date', '>=', now()->subDays(3));
                })
                ->count();

            if ($criticalCases > 0) {
                Notification::make()
                    ->title('Casos Críticos Sin Seguimiento')
                    ->body("{$criticalCases} casos de intento de suicidio activos sin seguimiento en 3 días.")
                    ->danger()
                    ->persistent()
                    ->actions([
                        Action::make('revisar')
                            ->button()
                            ->url(route('filament.admin.resources.suicide-attempts.index'))
                            ->label('Revisar Casos'),
                    ])
                    ->sendToDatabase($user);
            }
        }

        // Resumen semanal (solo lunes)
        if (now()->dayOfWeek === 1 && $user->can('view_analytics')) {
            $weeklyStats = [
                'completed_followups' => MonthlyFollowup::whereBetween('followup_date', [
                    now()->subWeek()->startOfWeek(),
                    now()->subWeek()->endOfWeek()
                ])->where('status', 'completed')->count(),
                'pending_followups' => MonthlyFollowup::where('status', 'pending')->count(),
            ];

            Notification::make()
                ->title('Resumen Semanal')
                ->body("La semana pasada: {$weeklyStats['completed_followups']} seguimientos completados. Pendientes: {$weeklyStats['pending_followups']}")
                ->info()
                ->actions([
                    Action::make('ver_dashboard')
                        ->button()
                        ->url(route('filament.admin.pages.dashboard'))
                        ->label('Ver Dashboard'),
                ])
                ->sendToDatabase($user);
        }

        $this->line("  Alertas enviadas a: {$user->name} ({$user->email})");
    }
}
