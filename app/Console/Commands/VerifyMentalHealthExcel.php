<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class VerifyMentalHealthExcel extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'mental-health:verify {file : Ruta del archivo Excel}';

    /**
     * The console command description.
     */
    protected $description = 'Verificar archivo Excel antes de importar - encuentra problemas potenciales';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("❌ El archivo no existe: {$filePath}");
            return 1;
        }

        $this->info("🔍 Verificando archivo: {$filePath}");
        $this->newLine();

        try {
            $reader = IOFactory::createReader('Xlsx');
            $spreadsheet = $reader->load($filePath);
            $sheetNames = $spreadsheet->getSheetNames();

            $this->info("📊 Hojas encontradas: " . implode(', ', $sheetNames));
            $this->newLine();

            $problems = [];
            $totalRows = 0;

            // Verificar cada hoja
            foreach ($sheetNames as $sheetName) {
                if (in_array($sheetName, ['TRASTORNOS 2025', 'EVENTO 356 2025', 'CONSUMO SPA 2025'])) {
                    $sheetProblems = $this->verifySheet($spreadsheet, $sheetName);
                    $problems = array_merge($problems, $sheetProblems);
                }
            }

            // Mostrar resumen
            $this->showSummary($problems);

            if (empty($problems)) {
                $this->info("✅ El archivo parece estar en buen estado para importar!");
                return 0;
            } else {
                $this->warn("⚠️  Se encontraron algunos problemas que podrían causar errores en la importación.");
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("❌ Error verificando archivo: " . $e->getMessage());
            return 1;
        }
    }

    private function verifySheet($spreadsheet, $sheetName)
    {
        $this->info("🔍 Verificando hoja: {$sheetName}");
        
        $sheet = $spreadsheet->getSheetByName($sheetName);
        $data = $sheet->toArray();
        
        if (empty($data)) {
            return ["{$sheetName}: Hoja vacía"];
        }

        $problems = [];
        $headers = array_shift($data); // Primera fila = headers
        
        $this->line("   📋 Filas de datos: " . count($data));
        $this->line("   📋 Columnas: " . count($headers));

        // Verificar problemas específicos según la hoja
        switch ($sheetName) {
            case 'TRASTORNOS 2025':
                $problems = array_merge($problems, $this->verifyTrastornosSheet($data, $headers));
                break;
            case 'EVENTO 356 2025':
                $problems = array_merge($problems, $this->verifyEvento356Sheet($data, $headers));
                break;
            case 'CONSUMO SPA 2025':
                $problems = array_merge($problems, $this->verifyConsumoSpaSheet($data, $headers));
                break;
        }

        if (empty($problems)) {
            $this->line("   ✅ Sin problemas detectados");
        } else {
            $this->line("   ⚠️  " . count($problems) . " problemas encontrados");
        }

        $this->newLine();
        return $problems;
    }

    private function verifyTrastornosSheet($data, $headers)
    {
        $problems = [];
        $emptyRows = 0;
        $longPhones = 0;
        $invalidDocuments = 0;
        $longNames = 0;

        foreach ($data as $rowIndex => $row) {
            $rowNumber = $rowIndex + 2; // +2 porque headers es fila 1

            // Encontrar índices de columnas importantes
            $docIndex = $this->findColumnIndex($headers, ['N. DOCUMENTO', 'N_DOCUMENTO', 'DOCUMENTO']);
            $nameIndex = $this->findColumnIndex($headers, ['NOMBRES Y APELLIDOS', 'NOMBRES_Y_APELLIDOS', 'NOMBRES']);
            $phoneIndex = $this->findColumnIndex($headers, ['TELEFONO', 'TELÉFONO', 'PHONE']);

            if ($docIndex === null || $nameIndex === null) {
                $problems[] = "TRASTORNOS: No se encontraron columnas requeridas (documento, nombre)";
                break;
            }

            $document = $row[$docIndex] ?? '';
            $name = $row[$nameIndex] ?? '';
            $phone = $row[$phoneIndex] ?? '';

            // Verificar fila vacía
            if (empty($document) && empty($name)) {
                $emptyRows++;
                continue;
            }

            // Verificar documento inválido
            if (!empty($document)) {
                $cleanDoc = preg_replace('/\D/', '', (string)$document);
                if (strlen($cleanDoc) < 6 || strlen($cleanDoc) > 15) {
                    $invalidDocuments++;
                }
            }

            // Verificar teléfono largo
            if (!empty($phone) && strlen((string)$phone) > 20) {
                $longPhones++;
            }

            // Verificar nombre largo
            if (!empty($name) && strlen((string)$name) > 255) {
                $longNames++;
            }
        }

        if ($emptyRows > 0) {
            $problems[] = "TRASTORNOS: {$emptyRows} filas vacías (se omitirán automáticamente)";
        }
        if ($longPhones > 0) {
            $problems[] = "TRASTORNOS: {$longPhones} teléfonos demasiado largos (se truncarán)";
        }
        if ($invalidDocuments > 0) {
            $problems[] = "TRASTORNOS: {$invalidDocuments} documentos con formato inválido (se omitirán)";
        }
        if ($longNames > 0) {
            $problems[] = "TRASTORNOS: {$longNames} nombres demasiado largos (se truncarán)";
        }

        return $problems;
    }

    private function verifyEvento356Sheet($data, $headers)
    {
        $problems = [];
        $emptyRows = 0;
        $invalidDocuments = 0;

        foreach ($data as $rowIndex => $row) {
            $docIndex = $this->findColumnIndex($headers, ['N. DOCUMENTO', 'N_DOCUMENTO', 'DOCUMENTO']);
            $nameIndex = $this->findColumnIndex($headers, ['NOMBRES Y APELLIDOS', 'NOMBRES_Y_APELLIDOS', 'NOMBRES']);

            if ($docIndex === null) {
                $problems[] = "EVENTO 356: No se encontró columna de documento";
                break;
            }

            $document = $row[$docIndex] ?? '';
            $name = $row[$nameIndex] ?? '';

            if (empty($document) && empty($name)) {
                $emptyRows++;
                continue;
            }

            if (!empty($document)) {
                $cleanDoc = preg_replace('/\D/', '', (string)$document);
                if (strlen($cleanDoc) < 6) {
                    $invalidDocuments++;
                }
            }
        }

        if ($emptyRows > 0) {
            $problems[] = "EVENTO 356: {$emptyRows} filas vacías (se omitirán automáticamente)";
        }
        if ($invalidDocuments > 0) {
            $problems[] = "EVENTO 356: {$invalidDocuments} documentos inválidos";
        }

        return $problems;
    }

    private function verifyConsumoSpaSheet($data, $headers)
    {
        $problems = [];
        $emptyRows = 0;

        foreach ($data as $rowIndex => $row) {
            $docIndex = $this->findColumnIndex($headers, ['N. DOCUMENTO', 'N_DOCUMENTO', 'DOCUMENTO']);
            $nameIndex = $this->findColumnIndex($headers, ['NOMBRE COMPLETO', 'NOMBRES']);

            if ($docIndex === null) {
                $problems[] = "CONSUMO SPA: No se encontró columna de documento";
                break;
            }

            $document = $row[$docIndex] ?? '';
            $name = $row[$nameIndex] ?? '';

            if (empty($document) && empty($name)) {
                $emptyRows++;
            }
        }

        if ($emptyRows > 0) {
            $problems[] = "CONSUMO SPA: {$emptyRows} filas vacías (se omitirán automáticamente)";
        }

        return $problems;
    }

    private function findColumnIndex($headers, $possibleNames)
    {
        foreach ($possibleNames as $name) {
            $index = array_search($name, $headers);
            if ($index !== false) {
                return $index;
            }
        }
        return null;
    }

    private function showSummary($problems)
    {
        $this->info("📋 RESUMEN DE VERIFICACIÓN:");
        $this->newLine();

        if (empty($problems)) {
            $this->info("✅ No se encontraron problemas significativos");
        } else {
            $this->warn("⚠️  Problemas encontrados:");
            foreach ($problems as $problem) {
                $this->line("   • {$problem}");
            }
        }

        $this->newLine();
        $this->info("💡 RECOMENDACIONES:");
        $this->line("   • Los teléfonos largos se truncarán automáticamente");
        $this->line("   • Las filas vacías se omitirán sin generar errores");
        $this->line("   • Los documentos inválidos se saltarán");
        $this->line("   • Ejecuta la migración para aumentar tamaños de campos si es necesario");
        $this->newLine();
    }
}