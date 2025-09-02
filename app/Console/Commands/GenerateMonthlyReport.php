<?php

// app/Console/Commands/GenerateMonthlyReport.php
namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\MonthlyFollowup;
use App\Models\MentalDisorder;
use App\Models\SuicideAttempt;
use App\Models\SubstanceConsumption;
use App\Services\ExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;

class GenerateMonthlyReport extends Command
{
    protected $signature = 'mental-health:generate-monthly-report 
                            {--month= : Mes específico (1-12)}
                            {--year= : Año específico}
                            {--send-email : Enviar por email a administradores}';
    
    protected $description = 'Genera reporte mensual automático del sistema';

    public function handle()
    {
        $month = $this->option('month') ?? now()->subMonth()->month;
        $year = $this->option('year') ?? now()->subMonth()->year;
        
        $this->info("Generando reporte mensual para {$month}/{$year}...");

        $stats = $this->generateStatistics($month, $year);
        $this->displayStatistics($stats, $month, $year);

        // Generar archivo Excel si hay servicio disponible
        if (class_exists(\App\Services\ExportService::class)) {
            try {
                $exportService = new ExportService();
                $fileName = $exportService->generateReport(
                    $year,
                    $month,
                    'consolidated',
                    'excel',
                    ['patient_demographics', 'followup_details'],
                    false
                );

                $this->info("📄 Reporte Excel generado: {$fileName}");

                // Enviar por email si se solicita
                if ($this->option('send-email')) {
                    $this->sendReportByEmail($fileName, $stats, $month, $year);
                }
            } catch (\Exception $e) {
                $this->error("Error generando Excel: " . $e->getMessage());
            }
        }

        $this->info('✅ Reporte mensual generado exitosamente.');
        return 0;
    }

    private function generateStatistics(int $month, int $year): array
    {
        return [
            'total_patients' => Patient::count(),
            'new_patients_month' => Patient::whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->count(),
            'mental_disorders' => MentalDisorder::count(),
            'mental_disorders_month' => MentalDisorder::whereMonth('admission_date', $month)
                ->whereYear('admission_date', $year)
                ->count(),
            'suicide_attempts' => SuicideAttempt::count(),
            'suicide_attempts_month' => SuicideAttempt::whereMonth('event_date', $month)
                ->whereYear('event_date', $year)
                ->count(),
            'substance_consumptions' => SubstanceConsumption::count(),
            'substance_consumptions_month' => SubstanceConsumption::whereMonth('admission_date', $month)
                ->whereYear('admission_date', $year)
                ->count(),
            'followups_completed' => MonthlyFollowup::where('month', $month)
                ->where('year', $year)
                ->where('status', 'completed')
                ->count(),
            'followups_pending' => MonthlyFollowup::where('status', 'pending')->count(),
        ];
    }

    private function displayStatistics(array $stats, int $month, int $year): void
    {
        $monthName = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ][$month];

        $this->info("\n📊 REPORTE SISTEMA SALUD MENTAL - {$monthName} {$year}");
        $this->info('=================================================');
        $this->line("👥 Pacientes Total: {$stats['total_patients']}");
        $this->line("📝 Pacientes Nuevos ({$monthName}): {$stats['new_patients_month']}");
        $this->info('');
        $this->line("❤️  Trastornos Mentales Total: {$stats['mental_disorders']}");
        $this->line("📅 Trastornos del Mes: {$stats['mental_disorders_month']}");
        $this->info('');
        $this->line("⚠️  Intentos Suicidio Total: {$stats['suicide_attempts']}");
        $this->line("🚨 Intentos del Mes: {$stats['suicide_attempts_month']}");
        $this->info('');
        $this->line("🧪 Consumos SPA Total: {$stats['substance_consumptions']}");
        $this->line("💊 Consumos del Mes: {$stats['substance_consumptions_month']}");
        $this->info('');
        $this->line("✅ Seguimientos Completados ({$monthName}): {$stats['followups_completed']}");
        $this->line("⏳ Seguimientos Pendientes: {$stats['followups_pending']}");
        $this->info('=================================================');
    }

    private function sendReportByEmail(string $fileName, array $stats, int $month, int $year): void
    {
        // Implementar envío de email a administradores
        $this->info('📧 Función de email pendiente de implementar');
    }
}