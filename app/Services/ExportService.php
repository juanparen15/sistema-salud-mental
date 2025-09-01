<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\MentalDisorder;
use App\Models\SuicideAttempt;
use App\Models\SubstanceConsumption;
use App\Models\MonthlyFollowup;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;

class ExportService
{
    public function generateReport(
        int $year,
        int $month,
        string $reportType,
        string $format = 'excel',
        array $includeDetails = [],
        bool $includeInactive = false
    ): string {
        $spreadsheet = new Spreadsheet();
        
        switch ($reportType) {
            case 'consolidated':
                $this->generateConsolidatedReport($spreadsheet, $year, $month, $includeDetails, $includeInactive);
                break;
            case 'mental_disorders':
                $this->generateMentalDisordersReport($spreadsheet, $year, $month, $includeDetails, $includeInactive);
                break;
            case 'suicide_attempts':
                $this->generateSuicideAttemptsReport($spreadsheet, $year, $month, $includeDetails, $includeInactive);
                break;
            case 'substance_consumption':
                $this->generateSubstanceConsumptionReport($spreadsheet, $year, $month, $includeDetails, $includeInactive);
                break;
            case 'followups_summary':
                $this->generateFollowupsSummaryReport($spreadsheet, $year, $month, $includeDetails, $includeInactive);
                break;
            case 'statistics':
                $this->generateStatisticsReport($spreadsheet, $year, $month);
                break;
            default:
                throw new \InvalidArgumentException("Tipo de reporte no válido: {$reportType}");
        }

        $fileName = $this->saveSpreadsheet($spreadsheet, $reportType, $year, $month, $format);
        
        return $fileName;
    }

    private function generateConsolidatedReport(
        Spreadsheet $spreadsheet,
        int $year,
        int $month,
        array $includeDetails,
        bool $includeInactive
    ): void {
        // Eliminar la hoja por defecto
        $spreadsheet->removeSheetByIndex(0);

        // 1. Resumen General
        $this->createSummarySheet($spreadsheet, $year, $month);

        // 2. Trastornos Mentales
        $this->createMentalDisordersSheet($spreadsheet, $year, $month, $includeDetails, $includeInactive);

        // 3. Intentos de Suicidio
        $this->createSuicideAttemptsSheet($spreadsheet, $year, $month, $includeDetails, $includeInactive);

        // 4. Consumo SPA
        $this->createSubstanceConsumptionSheet($spreadsheet, $year, $month, $includeDetails, $includeInactive);

        // 5. Seguimientos
        if (in_array('followup_details', $includeDetails)) {
            $this->createFollowupsSheet($spreadsheet, $year, $month, $includeInactive);
        }
    }

    private function createSummarySheet(Spreadsheet $spreadsheet, int $year, int $month): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Resumen General');

        // Encabezados
        $sheet->setCellValue('A1', 'REPORTE SISTEMA DE SALUD MENTAL');
        $sheet->setCellValue('A2', "Periodo: " . ($month == 0 ? "Todo {$year}" : $this->getMonthName($month) . " {$year}"));
        $sheet->setCellValue('A3', 'Generado: ' . now()->format('d/m/Y H:i'));

        // Estadísticas generales
        $sheet->setCellValue('A5', 'ESTADÍSTICAS GENERALES');
        
        $query = $month == 0 ? 
            MonthlyFollowup::where('year', $year) : 
            MonthlyFollowup::where('year', $year)->where('month', $month);

        $stats = [
            ['Métrica', 'Cantidad'],
            ['Total Pacientes', Patient::count()],
            ['Casos Trastornos Mentales', MentalDisorder::count()],
            ['Casos Intentos Suicidio', SuicideAttempt::count()],
            ['Casos Consumo SPA', SubstanceConsumption::count()],
            ['Total Seguimientos', $query->count()],
            ['Seguimientos Completados', $query->where('status', 'completed')->count()],
            ['Seguimientos Pendientes', $query->where('status', 'pending')->count()],
        ];

        $row = 6;
        foreach ($stats as $stat) {
            $sheet->setCellValue("A{$row}", $stat[0]);
            $sheet->setCellValue("B{$row}", $stat[1]);
            $row++;
        }

        // Aplicar estilos
        $this->applyHeaderStyles($sheet, 'A1:B1');
        $this->applyHeaderStyles($sheet, 'A6:B6');
    }

    private function createMentalDisordersSheet(
        Spreadsheet $spreadsheet, 
        int $year, 
        int $month,
        array $includeDetails,
        bool $includeInactive
    ): void {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Trastornos Mentales');

        // Obtener datos
        $query = MentalDisorder::with(['patient', 'followups' => function ($q) use ($year, $month) {
            $q->where('year', $year);
            if ($month > 0) {
                $q->where('month', $month);
            }
        }]);

        if (!$includeInactive) {
            $query->where('status', 'active');
        }

        $disorders = $query->get();

        // Encabezados
        $headers = [
            'Documento', 'Nombre Paciente', 'Código Diagnóstico', 'Descripción Diagnóstico',
            'Fecha Ingreso', 'Tipo Ingreso', 'Estado', 'Seguimientos'
        ];

        if (in_array('diagnosis_codes', $includeDetails)) {
            $headers[] = 'Tipo Diagnóstico';
            $headers[] = 'Fecha Diagnóstico';
        }

        $row = 1;
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, $row, $header);
        }

        // Datos
        $row = 2;
        foreach ($disorders as $disorder) {
            $col = 1;
            $sheet->setCellValueByColumnAndRow($col++, $row, $disorder->patient->document_number);
            $sheet->setCellValueByColumnAndRow($col++, $row, $disorder->patient->full_name);
            $sheet->setCellValueByColumnAndRow($col++, $row, $disorder->diagnosis_code);
            $sheet->setCellValueByColumnAndRow($col++, $row, $disorder->diagnosis_description);
            $sheet->setCellValueByColumnAndRow($col++, $row, $disorder->admission_date?->format('d/m/Y'));
            $sheet->setCellValueByColumnAndRow($col++, $row, $disorder->admission_type);
            $sheet->setCellValueByColumnAndRow($col++, $row, $disorder->status);
            $sheet->setCellValueByColumnAndRow($col++, $row, $disorder->followups->count());

            if (in_array('diagnosis_codes', $includeDetails)) {
                $sheet->setCellValueByColumnAndRow($col++, $row, $disorder->diagnosis_type);
                $sheet->setCellValueByColumnAndRow($col++, $row, $disorder->diagnosis_date?->format('d/m/Y'));
            }

            $row++;
        }

        $this->applyHeaderStyles($sheet, 'A1:' . chr(64 + count($headers)) . '1');
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(40);
    }

    private function createSuicideAttemptsSheet(
        Spreadsheet $spreadsheet,
        int $year,
        int $month,
        array $includeDetails,
        bool $includeInactive
    ): void {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Intentos Suicidio');

        $query = SuicideAttempt::with(['patient', 'followups' => function ($q) use ($year, $month) {
            $q->where('year', $year);
            if ($month > 0) {
                $q->where('month', $month);
            }
        }]);

        if (!$includeInactive) {
            $query->where('status', 'active');
        }

        $attempts = $query->get();

        $headers = [
            'Documento', 'Nombre Paciente', 'Fecha Evento', 'Número Intentos',
            'Mecanismo', 'Factor Desencadenante', 'Estado', 'Seguimientos'
        ];

        if (in_array('risk_factors', $includeDetails)) {
            $headers[] = 'Factores de Riesgo';
        }

        $row = 1;
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, $row, $header);
        }

        $row = 2;
        foreach ($attempts as $attempt) {
            $col = 1;
            $sheet->setCellValueByColumnAndRow($col++, $row, $attempt->patient->document_number);
            $sheet->setCellValueByColumnAndRow($col++, $row, $attempt->patient->full_name);
            $sheet->setCellValueByColumnAndRow($col++, $row, $attempt->event_date?->format('d/m/Y'));
            $sheet->setCellValueByColumnAndRow($col++, $row, $attempt->attempt_number);
            $sheet->setCellValueByColumnAndRow($col++, $row, $attempt->mechanism);
            $sheet->setCellValueByColumnAndRow($col++, $row, $attempt->trigger_factor);
            $sheet->setCellValueByColumnAndRow($col++, $row, $attempt->status);
            $sheet->setCellValueByColumnAndRow($col++, $row, $attempt->followups->count());

            if (in_array('risk_factors', $includeDetails)) {
                $riskFactors = is_array($attempt->risk_factors) ? 
                    implode(', ', $attempt->risk_factors) : 
                    $attempt->risk_factors;
                $sheet->setCellValueByColumnAndRow($col++, $row, $riskFactors);
            }

            $row++;
        }

        $this->applyHeaderStyles($sheet, 'A1:' . chr(64 + count($headers)) . '1');
    }

    private function createSubstanceConsumptionSheet(
        Spreadsheet $spreadsheet,
        int $year,
        int $month,
        array $includeDetails,
        bool $includeInactive
    ): void {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Consumo SPA');

        $query = SubstanceConsumption::with(['patient', 'followups' => function ($q) use ($year, $month) {
            $q->where('year', $year);
            if ($month > 0) {
                $q->where('month', $month);
            }
        }]);

        if (!$includeInactive) {
            $query->where('status', 'active');
        }

        $consumptions = $query->get();

        $headers = [
            'Documento', 'Nombre Paciente', 'Fecha Ingreso', 'Diagnóstico',
            'Nivel Consumo', 'Estado', 'Seguimientos'
        ];

        if (in_array('substance_details', $includeDetails)) {
            $headers[] = 'Sustancias Utilizadas';
        }

        $row = 1;
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, $row, $header);
        }

        $row = 2;
        foreach ($consumptions as $consumption) {
            $col = 1;
            $sheet->setCellValueByColumnAndRow($col++, $row, $consumption->patient->document_number);
            $sheet->setCellValueByColumnAndRow($col++, $row, $consumption->patient->full_name);
            $sheet->setCellValueByColumnAndRow($col++, $row, $consumption->admission_date?->format('d/m/Y'));
            $sheet->setCellValueByColumnAndRow($col++, $row, $consumption->diagnosis);
            $sheet->setCellValueByColumnAndRow($col++, $row, $consumption->consumption_level);
            $sheet->setCellValueByColumnAndRow($col++, $row, $consumption->status);
            $sheet->setCellValueByColumnAndRow($col++, $row, $consumption->followups->count());

            if (in_array('substance_details', $includeDetails)) {
                $substances = is_array($consumption->substances_used) ? 
                    implode(', ', $consumption->substances_used) : 
                    $consumption->substances_used;
                $sheet->setCellValueByColumnAndRow($col++, $row, $substances);
            }

            $row++;
        }

        $this->applyHeaderStyles($sheet, 'A1:' . chr(64 + count($headers)) . '1');
    }

    private function createFollowupsSheet(
        Spreadsheet $spreadsheet,
        int $year,
        int $month,
        bool $includeInactive
    ): void {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Seguimientos');

        $query = MonthlyFollowup::with(['followupable.patient', 'user'])
            ->where('year', $year);

        if ($month > 0) {
            $query->where('month', $month);
        }

        $followups = $query->get();

        $headers = [
            'Documento', 'Paciente', 'Tipo Caso', 'Fecha', 'Mes', 'Estado',
            'Descripción', 'Realizado Por'
        ];

        $row = 1;
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, $row, $header);
        }

        $row = 2;
        foreach ($followups as $followup) {
            $col = 1;
            $patient = $followup->followupable?->patient;
            
            $sheet->setCellValueByColumnAndRow($col++, $row, $patient?->document_number ?? 'N/A');
            $sheet->setCellValueByColumnAndRow($col++, $row, $patient?->full_name ?? 'N/A');
            $sheet->setCellValueByColumnAndRow($col++, $row, $followup->case_type);
            $sheet->setCellValueByColumnAndRow($col++, $row, $followup->followup_date?->format('d/m/Y'));
            $sheet->setCellValueByColumnAndRow($col++, $row, $this->getMonthName($followup->month));
            $sheet->setCellValueByColumnAndRow($col++, $row, $followup->status);
            $sheet->setCellValueByColumnAndRow($col++, $row, substr($followup->description ?? '', 0, 100));
            $sheet->setCellValueByColumnAndRow($col++, $row, $followup->user?->name ?? 'Sistema');

            $row++;
        }

        $this->applyHeaderStyles($sheet, 'A1:H1');
    }

    public function getReportPreview(int $year, int $month, string $reportType): string
    {
        switch ($reportType) {
            case 'consolidated':
                $totalPatients = Patient::count();
                $mentalDisorders = MentalDisorder::count();
                $suicideAttempts = SuicideAttempt::count();
                $substanceConsumption = SubstanceConsumption::count();
                
                return "Reporte Consolidado {$year}" . ($month > 0 ? " - " . $this->getMonthName($month) : "") . 
                       "\n• {$totalPatients} pacientes\n• {$mentalDisorders} trastornos mentales\n• {$suicideAttempts} intentos suicidio\n• {$substanceConsumption} casos SPA";
                       
            case 'mental_disorders':
                $count = MentalDisorder::count();
                return "Reporte Trastornos Mentales: {$count} casos registrados";
                
            case 'suicide_attempts':
                $count = SuicideAttempt::count();
                return "Reporte Intentos Suicidio: {$count} casos registrados";
                
            case 'substance_consumption':
                $count = SubstanceConsumption::count();
                return "Reporte Consumo SPA: {$count} casos registrados";
                
            default:
                return "Vista previa no disponible para este tipo de reporte";
        }
    }

    private function saveSpreadsheet(
        Spreadsheet $spreadsheet,
        string $reportType,
        int $year,
        int $month,
        string $format
    ): string {
        $monthName = $month == 0 ? 'Anual' : $this->getMonthName($month);
        $fileName = "reporte_{$reportType}_{$year}_{$monthName}." . ($format === 'csv' ? 'csv' : 'xlsx');

        if ($format === 'csv') {
            $writer = new Csv($spreadsheet);
        } else {
            $writer = new Xlsx($spreadsheet);
        }

        $filePath = storage_path("app/public/{$fileName}");
        $writer->save($filePath);

        return $fileName;
    }

    private function applyHeaderStyles($sheet, string $range): void
    {
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');

        $sheet->getStyle($range)->getFont()
            ->setBold(true)
            ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
    }

    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        
        return $months[$month] ?? "Mes {$month}";
    }

    // Métodos para otros tipos de reportes (implementar según necesidad)
    private function generateMentalDisordersReport($spreadsheet, $year, $month, $includeDetails, $includeInactive) {
        $this->createMentalDisordersSheet($spreadsheet, $year, $month, $includeDetails, $includeInactive);
    }

    private function generateSuicideAttemptsReport($spreadsheet, $year, $month, $includeDetails, $includeInactive) {
        $this->createSuicideAttemptsSheet($spreadsheet, $year, $month, $includeDetails, $includeInactive);
    }

    private function generateSubstanceConsumptionReport($spreadsheet, $year, $month, $includeDetails, $includeInactive) {
        $this->createSubstanceConsumptionSheet($spreadsheet, $year, $month, $includeDetails, $includeInactive);
    }

    private function generateFollowupsSummaryReport($spreadsheet, $year, $month, $includeDetails, $includeInactive) {
        $this->createFollowupsSheet($spreadsheet, $year, $month, $includeInactive);
    }

    private function generateStatisticsReport($spreadsheet, $year, $month) {
        $this->createSummarySheet($spreadsheet, $year, $month);
    }
}