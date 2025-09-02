<?php

// app/Console/Commands/CleanupSystem.php
namespace App\Console\Commands;

use App\Models\MonthlyFollowup;
use App\Models\Patient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupSystem extends Command
{
    protected $signature = 'mental-health:cleanup 
                            {--dry-run : Solo mostrar qué se eliminaría sin hacer cambios}
                            {--days=90 : Días de antigüedad para limpiar}';

    protected $description = 'Limpia registros antiguos y datos innecesarios del sistema';

    public function handle()
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("🧹 Iniciando limpieza del sistema...");
        $this->info("Configuración: {$days} días de antigüedad | Dry run: " . ($dryRun ? 'SÍ' : 'NO'));

        // Seguimientos duplicados
        $this->cleanupDuplicateFollowups($dryRun);

        // Registros huérfanos
        $this->cleanupOrphanRecords($dryRun);

        // Notificaciones antiguas
        $this->cleanupOldNotifications($days, $dryRun);

        $this->info('✅ Limpieza del sistema completada.');
        return 0;
    }

    private function cleanupDuplicateFollowups(bool $dryRun): void
    {
        $this->line('🔍 Buscando seguimientos duplicados...');

        $duplicates = DB::table('monthly_followups as mf1')
            ->join('monthly_followups as mf2', function ($join) {
                $join->on('mf1.followupable_type', '=', 'mf2.followupable_type')
                    ->on('mf1.followupable_id', '=', 'mf2.followupable_id')
                    ->on('mf1.year', '=', 'mf2.year')
                    ->on('mf1.month', '=', 'mf2.month')
                    ->where('mf1.id', '>', DB::raw('mf2.id'));
            })
            ->pluck('mf1.id');

        if ($duplicates->isEmpty()) {
            $this->info('✨ No se encontraron seguimientos duplicados.');
            return;
        }

        $count = $duplicates->count();
        $this->warn("⚠️ Encontrados {$count} seguimientos duplicados");

        if (!$dryRun) {
            MonthlyFollowup::whereIn('id', $duplicates)->delete();
            $this->info("🗑️ {$count} seguimientos duplicados eliminados.");
        } else {
            $this->info("🔍 [DRY RUN] Se eliminarían {$count} seguimientos duplicados.");
        }
    }

    private function cleanupOrphanRecords(bool $dryRun): void
    {
        $this->line('🔍 Buscando registros huérfanos...');

        // Seguimientos sin paciente válido
        $orphanFollowups = MonthlyFollowup::whereDoesntHave('followupable')->count();

        if ($orphanFollowups > 0) {
            $this->warn("⚠️ Encontrados {$orphanFollowups} seguimientos huérfanos");

            if (!$dryRun) {
                MonthlyFollowup::whereDoesntHave('followupable')->delete();
                $this->info("🗑️ {$orphanFollowups} seguimientos huérfanos eliminados.");
            } else {
                $this->info("🔍 [DRY RUN] Se eliminarían {$orphanFollowups} seguimientos huérfanos.");
            }
        } else {
            $this->info('✨ No se encontraron seguimientos huérfanos.');
        }
    }

    private function cleanupOldNotifications(int $days, bool $dryRun): void
    {
        $this->line('🔍 Buscando notificaciones antiguas...');

        $oldNotifications = DB::table('notifications')
            ->where('created_at', '<', now()->subDays($days))
            ->count();

        if ($oldNotifications > 0) {
            $this->warn("⚠️ Encontradas {$oldNotifications} notificaciones de más de {$days} días");

            if (!$dryRun) {
                DB::table('notifications')
                    ->where('created_at', '<', now()->subDays($days))
                    ->delete();
                $this->info("🗑️ {$oldNotifications} notificaciones antiguas eliminadas.");
            } else {
                $this->info("🔍 [DRY RUN] Se eliminarían {$oldNotifications} notificaciones antiguas.");
            }
        } else {
            $this->info('✨ No se encontraron notificaciones antiguas para limpiar.');
        }
    }
}
