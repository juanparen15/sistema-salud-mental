<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\ProcessMonthlyFollowups;
use App\Jobs\DetectHighRiskPatients;
use App\Jobs\GenerateMonthlyReport;
use App\Jobs\BackupDatabase;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Procesar seguimientos diariamente a las 8:00 AM
        $schedule->job(new ProcessMonthlyFollowups())
            ->dailyAt('08:00')
            ->name('process-followups')
            ->withoutOverlapping();

        // Detectar pacientes de alto riesgo cada 6 horas
        $schedule->job(new DetectHighRiskPatients())
            ->everySixHours()
            ->name('detect-high-risk')
            ->withoutOverlapping();

        // Generar reporte mensual el primer día de cada mes
        $schedule->job(new GenerateMonthlyReport())
            ->monthlyOn(1, '00:01')
            ->name('generate-monthly-report');

        // Backup de base de datos diario a las 2:00 AM
        $schedule->command('backup:run')
            ->dailyAt('02:00')
            ->name('database-backup');

        // Limpiar notificaciones antiguas (más de 90 días)
        $schedule->command('notifications:clean')
            ->weekly()
            ->sundays()
            ->at('03:00');

        // Optimizar la aplicación semanalmente
        $schedule->command('optimize:clear')
            ->weekly()
            ->sundays()
            ->at('04:00');

        // Generar reportes mensuales
        // $schedule->job(new \App\Jobs\GenerateMonthlyReport())
        //     ->monthly();

        // Notificaciones automáticas diarias
        $schedule->call(function () {
            app(\App\Services\NotificationService::class)->scheduleAutomaticNotifications();
        })->daily();

        // Resumen mensual
        $schedule->call(function () {
            app(\App\Services\NotificationService::class)->sendMonthlySummary();
        })->monthlyOn(1, '08:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

    protected $commands = [
        // \App\Console\Commands\ImportPatientsCommand::class,
        Commands\SendDailyAlerts::class,
    ];

    // protected $commands = [
    //     \App\Console\Commands\ImportMentalHealthData::class,
    // ];

    protected $middlewareAliases = [
        // ...otros middlewares
        'filament.permissions' => \App\Http\Middleware\CheckFilamentPermissions::class,
    ];
}
