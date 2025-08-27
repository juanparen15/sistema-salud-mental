<?php

namespace App\Console\Commands;

use App\Imports\PatientsImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ImportPatientsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patients:import {file} {--disk=public}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importar pacientes desde un archivo Excel sin crear duplicados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        $disk = $this->option('disk');

        if (!Storage::disk($disk)->exists($filePath)) {
            $this->error("El archivo {$filePath} no existe en el disco {$disk}");
            return 1;
        }

        $this->info("Iniciando importación desde: {$filePath}");
        $this->newLine();

        try {
            $import = new PatientsImport();
            
            $this->withProgressBar(
                range(1, 100),
                function () use ($import, $filePath, $disk) {
                    Excel::import($import, $filePath, $disk);
                }
            );

            $this->newLine(2);
            $this->info('¡Importación completada exitosamente!');
            $this->newLine();

            // Mostrar estadísticas
            $this->line("📊 <info>Estadísticas de importación:</info>");
            $this->line("✅ Pacientes nuevos creados: <fg=green>{$import->getImportedCount()}</>");
            $this->line("🔄 Pacientes actualizados: <fg=yellow>{$import->getUpdatedCount()}</>");
            $this->line("⏭️  Registros omitidos: <fg=gray>{$import->getSkippedCount()}</>");

            if (count($import->getErrors()) > 0) {
                $this->newLine();
                $this->warn("⚠️  Se encontraron algunos errores:");
                foreach ($import->getErrors() as $error) {
                    $this->line("   • {$error}");
                }
            }

        } catch (\Exception $e) {
            $this->error("❌ Error durante la importación: " . $e->getMessage());
            $this->error("Detalles: " . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}