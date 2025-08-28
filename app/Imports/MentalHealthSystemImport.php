<?php

// namespace App\Imports;

// use App\Models\Patient;
// use App\Models\MonthlyFollowup;
// use Illuminate\Support\Collection;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
// use Maatwebsite\Excel\Concerns\WithMultipleSheets;
// use Maatwebsite\Excel\Concerns\ToCollection;
// use Maatwebsite\Excel\Concerns\WithHeadingRow;
// use Carbon\Carbon;

// class MentalHealthSystemImport implements WithMultipleSheets
// {
//     protected $importedCount = 0;
//     protected $updatedCount = 0;
//     protected $skippedCount = 0;
//     protected $errors = [];
//     protected $followupsCreated = 0;

//     public function sheets(): array
//     {
//         return [
//             'TRASTORNOS 2025' => new TrastornosSheet($this),
//             'EVENTO 356 2025' => new Evento356Sheet($this),
//             'CONSUMO SPA 2025' => new ConsumoSpaSheet($this),
//         ];
//     }

//     // Métodos para actualizar contadores desde las hojas
//     public function incrementImported() { $this->importedCount++; }
//     public function incrementUpdated() { $this->updatedCount++; }
//     public function incrementSkipped() { $this->skippedCount++; }
//     public function incrementFollowups() { $this->followupsCreated++; }
//     public function addError($error) { $this->errors[] = $error; }

//     // Getters para estadísticas
//     public function getImportedCount(): int { return $this->importedCount; }
//     public function getUpdatedCount(): int { return $this->updatedCount; }
//     public function getSkippedCount(): int { return $this->skippedCount; }
//     public function getFollowupsCreated(): int { return $this->followupsCreated; }
//     public function getErrors(): array { return $this->errors; }
// }

// // ==================== HOJA TRASTORNOS ====================
// class TrastornosSheet implements ToCollection, WithHeadingRow
// {
//     protected $parent;

//     public function __construct(MentalHealthSystemImport $parent)
//     {
//         $this->parent = $parent;
//     }

//     public function collection(Collection $collection)
//     {
//         Log::info("Procesando hoja TRASTORNOS 2025 - {$collection->count()} registros");

//         foreach ($collection as $index => $row) {
//             $this->processRow($row, $index + 2, 'trastorno');
//         }
//     }

//     private function processRow(Collection $row, int $rowNumber, string $eventType)
//     {
//         try {
//             // Extraer datos básicos del paciente
//             $documentNumber = $this->cleanString($row['n_documento']);
            
//             if (empty($documentNumber)) {
//                 $this->parent->incrementSkipped();
//                 $this->parent->addError("TRASTORNOS Fila {$rowNumber}: Sin número de documento");
//                 return;
//             }

//             $fullName = $this->cleanString($row['nombres_y_apellidos']);
//             if (empty($fullName)) {
//                 $this->parent->incrementSkipped();
//                 $this->parent->addError("TRASTORNOS Fila {$rowNumber}: Sin nombre (doc: {$documentNumber})");
//                 return;
//             }

//             // Crear o actualizar paciente
//             $patient = $this->createOrUpdatePatient($row, $rowNumber, $eventType);
//             if (!$patient) return;

//             // Procesar seguimientos mensuales
//             $this->processMonthlyFollowups($patient, $row, $rowNumber, $eventType);

//         } catch (\Exception $e) {
//             $this->parent->addError("TRASTORNOS Fila {$rowNumber}: " . $e->getMessage());
//             Log::error("Error en TRASTORNOS fila {$rowNumber}: " . $e->getMessage());
//         }
//     }

//     private function createOrUpdatePatient(Collection $row, int $rowNumber, string $eventType)
//     {
//         $documentNumber = $this->cleanString($row['n_documento']);
//         $patient = Patient::where('document_number', $documentNumber)->first();

//         $patientData = [
//             'document_number' => $documentNumber,
//             'document_type' => $this->mapDocumentType($row['tipo_de_documento']),
//             'full_name' => $this->cleanString($row['nombres_y_apellidos']),
//             'gender' => $this->mapGender($row['sex0']),
//             'birth_date' => $this->parseDate($row['fecha_de_nacimiento'])?->format('Y-m-d'),
//             'phone' => $this->cleanString($row['telefono']),
//             'address' => $this->cleanString($row['direccion']),
//             'village' => $this->cleanString($row['vereda']),
//             'eps_code' => $this->cleanString($row['eps_codigo']),
//             'eps_name' => $this->cleanString($row['eps_nombre']),
//             'status' => 'active',
//         ];

//         // Filtrar valores nulos
//         $patientData = array_filter($patientData, fn($value) => $value !== null);

//         try {
//             if ($patient) {
//                 $patient->update($patientData);
//                 $this->parent->incrementUpdated();
//             } else {
//                 $patient = Patient::create($patientData);
//                 $this->parent->incrementImported();
//             }

//             return $patient;

//         } catch (\Exception $e) {
//             $this->parent->addError("TRASTORNOS Fila {$rowNumber}: Error creando paciente - " . $e->getMessage());
//             return null;
//         }
//     }

//     private function processMonthlyFollowups(Patient $patient, Collection $row, int $rowNumber, string $eventType)
//     {
//         $months = [
//             'enero_2025' => 1, 'febrero_2025' => 2, 'marzo_2025' => 3, 'abril_2025' => 4,
//             'mayo_2025' => 5, 'junio_2025' => 6, 'julio_2025' => 7, 'agosto_2025' => 8,
//             'septiembre_2025' => 9, 'octubre_2025' => 10, 'noviembre_2025' => 11, 'diciembre_2025' => 12
//         ];

//         foreach ($months as $columnName => $monthNumber) {
//             $followupData = $this->cleanString($row[$columnName]);
            
//             if (empty($followupData)) continue;

//             // Verificar si ya existe seguimiento para este mes
//             $existingFollowup = MonthlyFollowup::where('followupable_id', $patient->id)
//                 ->where('followupable_type', Patient::class)
//                 ->where('year', 2025)
//                 ->where('month', $monthNumber)
//                 ->first();

//             if ($existingFollowup) continue; // Ya existe

//             // Crear seguimiento
//             $description = "Seguimiento TRASTORNO - " . $followupData;
            
//             // Agregar información específica de trastornos
//             if (!empty($row['diagnostico'])) {
//                 $description .= " | Diagnóstico: " . $this->cleanString($row['diagnostico']);
//             }
//             if (!empty($row['observacion_adicional'])) {
//                 $description .= " | Obs: " . $this->cleanString($row['observacion_adicional']);
//             }

//             MonthlyFollowup::create([
//                 'followupable_id' => $patient->id,
//                 'followupable_type' => Patient::class,
//                 'followup_date' => Carbon::create(2025, $monthNumber, 15)->format('Y-m-d'), // Día 15 del mes
//                 'year' => 2025,
//                 'month' => $monthNumber,
//                 'description' => $description,
//                 'status' => 'completed',
//                 'actions_taken' => json_encode(['Seguimiento de trastorno mental']),
//                 'performed_by' => auth()->id() ?? 1,
//             ]);

//             $this->parent->incrementFollowups();
//         }
//     }

//     private function cleanString($value): ?string
//     {
//         return empty($value) ? null : trim(strip_tags((string) $value));
//     }

//     private function mapDocumentType($value): string
//     {
//         if (empty($value)) return 'CC';
//         $type = strtoupper(trim($value));
//         return in_array($type, ['CC', 'TI', 'CE', 'PA', 'RC']) ? $type : 'CC';
//     }

//     private function mapGender($value): string
//     {
//         if (empty($value)) return 'Otro';
//         $gender = strtoupper(trim($value));
//         if (in_array($gender, ['M', 'MASCULINO'])) return 'Masculino';
//         if (in_array($gender, ['F', 'FEMENINO'])) return 'Femenino';
//         return 'Otro';
//     }

//     private function parseDate($value): ?Carbon
//     {
//         if (empty($value)) return null;
//         try {
//             if (is_numeric($value)) {
//                 return Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays($value);
//             }
//             return Carbon::parse($value);
//         } catch (\Exception $e) {
//             return null;
//         }
//     }
// }

// // ==================== HOJA EVENTO 356 ====================
// class Evento356Sheet implements ToCollection, WithHeadingRow
// {
//     protected $parent;

//     public function __construct(MentalHealthSystemImport $parent)
//     {
//         $this->parent = $parent;
//     }

//     public function collection(Collection $collection)
//     {
//         Log::info("Procesando hoja EVENTO 356 2025 - {$collection->count()} registros");

//         foreach ($collection as $index => $row) {
//             $this->processRow($row, $index + 2, 'intento_suicidio');
//         }
//     }

//     private function processRow(Collection $row, int $rowNumber, string $eventType)
//     {
//         try {
//             $documentNumber = $this->cleanString($row['n_documento']);
            
//             if (empty($documentNumber)) {
//                 $this->parent->incrementSkipped();
//                 $this->parent->addError("EVENTO 356 Fila {$rowNumber}: Sin número de documento");
//                 return;
//             }

//             $patient = $this->createOrUpdatePatient($row, $rowNumber, $eventType);
//             if (!$patient) return;

//             $this->processMonthlyFollowups($patient, $row, $rowNumber, $eventType);

//         } catch (\Exception $e) {
//             $this->parent->addError("EVENTO 356 Fila {$rowNumber}: " . $e->getMessage());
//         }
//     }

//     private function createOrUpdatePatient(Collection $row, int $rowNumber, string $eventType)
//     {
//         $documentNumber = $this->cleanString($row['n_documento']);
//         $patient = Patient::where('document_number', $documentNumber)->first();

//         $patientData = [
//             'document_number' => $documentNumber,
//             'document_type' => $this->mapDocumentType($row['tipo_doc']),
//             'full_name' => $this->cleanString($row['nombres_y_apellidos']),
//             'gender' => $this->mapGender($row['sexo']),
//             'birth_date' => $this->parseDate($row['fecha_de_nacimiento'])?->format('Y-m-d'),
//             'phone' => $this->cleanString($row['telefono']),
//             'address' => $this->cleanString($row['direccion']),
//             'neighborhood' => $this->cleanString($row['barrio']),
//             'village' => $this->cleanString($row['vereda']),
//             'status' => 'active',
//         ];

//         $patientData = array_filter($patientData, fn($value) => $value !== null);

//         try {
//             if ($patient) {
//                 $patient->update($patientData);
//                 $this->parent->incrementUpdated();
//             } else {
//                 $patient = Patient::create($patientData);
//                 $this->parent->incrementImported();
//             }

//             return $patient;
//         } catch (\Exception $e) {
//             $this->parent->addError("EVENTO 356 Fila {$rowNumber}: Error creando paciente - " . $e->getMessage());
//             return null;
//         }
//     }

//     private function processMonthlyFollowups(Patient $patient, Collection $row, int $rowNumber, string $eventType)
//     {
//         $months = [
//             'enero_2025' => 1, 'febrero_2025' => 2, 'marzo_2025' => 3, 'abril_2025' => 4,
//             'mayo_2025' => 5, 'junio_2025' => 6, 'julio_2025' => 7, 'agosto_2025' => 8,
//             'septiembre_2025' => 9, 'octubre_2025' => 10, 'noviembre_2025' => 11, 'diciembre_2025' => 12
//         ];

//         foreach ($months as $columnName => $monthNumber) {
//             $followupData = $this->cleanString($row[$columnName]);
            
//             if (empty($followupData)) continue;

//             $existingFollowup = MonthlyFollowup::where('followupable_id', $patient->id)
//                 ->where('followupable_type', Patient::class)
//                 ->where('year', 2025)
//                 ->where('month', $monthNumber)
//                 ->first();

//             if ($existingFollowup) continue;

//             // Crear descripción específica para intento de suicidio
//             $description = "Seguimiento INTENTO SUICIDIO - " . $followupData;
            
//             if (!empty($row['n_intentos'])) {
//                 $description .= " | N° Intentos: " . $this->cleanString($row['n_intentos']);
//             }
//             if (!empty($row['desencadenante'])) {
//                 $description .= " | Desencadenante: " . $this->cleanString($row['desencadenante']);
//             }
//             if (!empty($row['mecanismo'])) {
//                 $description .= " | Mecanismo: " . $this->cleanString($row['mecanismo']);
//             }

//             $actions = ['Seguimiento intento suicidio'];
//             if (!empty($row['factores_de_riesgo'])) {
//                 $actions[] = 'Evaluación factores de riesgo: ' . $this->cleanString($row['factores_de_riesgo']);
//             }

//             MonthlyFollowup::create([
//                 'followupable_id' => $patient->id,
//                 'followupable_type' => Patient::class,
//                 'followup_date' => Carbon::create(2025, $monthNumber, 15)->format('Y-m-d'),
//                 'year' => 2025,
//                 'month' => $monthNumber,
//                 'description' => $description,
//                 'status' => 'completed',
//                 'actions_taken' => json_encode($actions),
//                 'performed_by' => auth()->id() ?? 1,
//             ]);

//             $this->parent->incrementFollowups();
//         }
//     }

//     // Métodos helper reutilizados
//     private function cleanString($value): ?string { return empty($value) ? null : trim(strip_tags((string) $value)); }
//     private function mapDocumentType($value): string { return empty($value) ? 'CC' : (in_array(strtoupper(trim($value)), ['CC', 'TI', 'CE', 'PA', 'RC']) ? strtoupper(trim($value)) : 'CC'); }
//     private function mapGender($value): string { 
//         if (empty($value)) return 'Otro';
//         $gender = strtoupper(trim($value));
//         if (in_array($gender, ['M', 'MASCULINO'])) return 'Masculino';
//         if (in_array($gender, ['F', 'FEMENINO'])) return 'Femenino';
//         return 'Otro';
//     }
//     private function parseDate($value): ?Carbon { 
//         if (empty($value)) return null;
//         try { return is_numeric($value) ? Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays($value) : Carbon::parse($value); } 
//         catch (\Exception $e) { return null; }
//     }
// }

// // ==================== HOJA CONSUMO SPA ====================
// class ConsumoSpaSheet implements ToCollection, WithHeadingRow
// {
//     protected $parent;

//     public function __construct(MentalHealthSystemImport $parent)
//     {
//         $this->parent = $parent;
//     }

//     public function collection(Collection $collection)
//     {
//         Log::info("Procesando hoja CONSUMO SPA 2025 - {$collection->count()} registros");

//         foreach ($collection as $index => $row) {
//             $this->processRow($row, $index + 2, 'consumo_spa');
//         }
//     }

//     private function processRow(Collection $row, int $rowNumber, string $eventType)
//     {
//         try {
//             $documentNumber = $this->cleanString($row['n_documento']);
            
//             if (empty($documentNumber)) {
//                 $this->parent->incrementSkipped();
//                 $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: Sin número de documento");
//                 return;
//             }

//             $patient = $this->createOrUpdatePatient($row, $rowNumber, $eventType);
//             if (!$patient) return;

//             $this->processMonthlyFollowups($patient, $row, $rowNumber, $eventType);

//         } catch (\Exception $e) {
//             $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: " . $e->getMessage());
//         }
//     }

//     private function createOrUpdatePatient(Collection $row, int $rowNumber, string $eventType)
//     {
//         $documentNumber = $this->cleanString($row['n_documento']);
//         $patient = Patient::where('document_number', $documentNumber)->first();

//         $patientData = [
//             'document_number' => $documentNumber,
//             'document_type' => $this->mapDocumentType($row['tipo_doc']),
//             'full_name' => $this->cleanString($row['nombre_completo']),
//             'gender' => $this->mapGender($row['sexo']),
//             'birth_date' => $this->parseDate($row['fecha_de_nacimiento'])?->format('Y-m-d'),
//             'phone' => $this->cleanString($row['telefono']),
//             'eps_name' => $this->cleanString($row['eps'] ?? $row['nombre']),
//             'status' => 'active',
//         ];

//         $patientData = array_filter($patientData, fn($value) => $value !== null);

//         try {
//             if ($patient) {
//                 $patient->update($patientData);
//                 $this->parent->incrementUpdated();
//             } else {
//                 $patient = Patient::create($patientData);
//                 $this->parent->incrementImported();
//             }

//             return $patient;
//         } catch (\Exception $e) {
//             $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: Error creando paciente - " . $e->getMessage());
//             return null;
//         }
//     }

//     private function processMonthlyFollowups(Patient $patient, Collection $row, int $rowNumber, string $eventType)
//     {
//         $months = [
//             'enero_2025' => 1, 'febrero_2025' => 2, 'marzo_2025' => 3, 'abril_2025' => 4,
//             'mayo_2025' => 5, 'junio_2025' => 6, 'julio_2025' => 7, 'agosto_2025' => 8,
//             'septiembre_2025' => 9, 'octubre_2025' => 10, 'noviembre_2025' => 11, 'diciembre_2025' => 12
//         ];

//         foreach ($months as $columnName => $monthNumber) {
//             $followupData = $this->cleanString($row[$columnName]);
            
//             if (empty($followupData)) continue;

//             $existingFollowup = MonthlyFollowup::where('followupable_id', $patient->id)
//                 ->where('followupable_type', Patient::class)
//                 ->where('year', 2025)
//                 ->where('month', $monthNumber)
//                 ->first();

//             if ($existingFollowup) continue;

//             $description = "Seguimiento CONSUMO SPA - " . $followupData;
            
//             if (!empty($row['diagnostico'])) {
//                 $description .= " | Diagnóstico: " . $this->cleanString($row['diagnostico']);
//             }
//             if (!empty($row['observacion_adicional'])) {
//                 $description .= " | Obs: " . $this->cleanString($row['observacion_adicional']);
//             }

//             MonthlyFollowup::create([
//                 'followupable_id' => $patient->id,
//                 'followupable_type' => Patient::class,
//                 'followup_date' => Carbon::create(2025, $monthNumber, 15)->format('Y-m-d'),
//                 'year' => 2025,
//                 'month' => $monthNumber,
//                 'description' => $description,
//                 'status' => 'completed',
//                 'actions_taken' => json_encode(['Seguimiento consumo SPA']),
//                 'performed_by' => auth()->id() ?? 1,
//             ]);

//             $this->parent->incrementFollowups();
//         }
//     }

//     // Métodos helper
//     private function cleanString($value): ?string { return empty($value) ? null : trim(strip_tags((string) $value)); }
//     private function mapDocumentType($value): string { return empty($value) ? 'CC' : (in_array(strtoupper(trim($value)), ['CC', 'TI', 'CE', 'PA', 'RC']) ? strtoupper(trim($value)) : 'CC'); }
//     private function mapGender($value): string { 
//         if (empty($value)) return 'Otro';
//         $gender = strtoupper(trim($value));
//         if (in_array($gender, ['M', 'MASCULINO'])) return 'Masculino';
//         if (in_array($gender, ['F', 'FEMENINO'])) return 'Femenino';
//         return 'Otro';
//     }
//     private function parseDate($value): ?Carbon { 
//         if (empty($value)) return null;
//         try { return is_numeric($value) ? Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays($value) : Carbon::parse($value); } 
//         catch (\Exception $e) { return null; }
//     }
// }

namespace App\Imports;

use App\Models\Patient;
use App\Models\MonthlyFollowup;
use App\Models\MentalDisorder;
use App\Models\SuicideAttempt;
use App\Models\SubstanceConsumption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class MentalHealthSystemImport implements WithMultipleSheets
{
    protected $importedCount = 0;
    protected $updatedCount = 0;
    protected $skippedCount = 0;
    protected $errors = [];
    protected $followupsCreated = 0;
    protected $casesCreated = 0;

    public function sheets(): array
    {
        return [
            'TRASTORNOS 2025' => new TrastornosSheet($this),
            'EVENTO 356 2025' => new Evento356Sheet($this),
            'CONSUMO SPA 2025' => new ConsumoSpaSheet($this),
        ];
    }

    // Métodos para actualizar contadores
    public function incrementImported() { $this->importedCount++; }
    public function incrementUpdated() { $this->updatedCount++; }
    public function incrementSkipped() { $this->skippedCount++; }
    public function incrementFollowups() { $this->followupsCreated++; }
    public function incrementCases() { $this->casesCreated++; }
    public function addError($error) { $this->errors[] = $error; }

    // Getters para estadísticas
    public function getImportedCount(): int { return $this->importedCount; }
    public function getUpdatedCount(): int { return $this->updatedCount; }
    public function getSkippedCount(): int { return $this->skippedCount; }
    public function getFollowupsCreated(): int { return $this->followupsCreated; }
    public function getCasesCreated(): int { return $this->casesCreated; }
    public function getErrors(): array { return $this->errors; }
}

// ==================== HOJA TRASTORNOS COMPLETA ====================
class TrastornosSheet implements ToCollection, WithHeadingRow
{
    protected $parent;

    public function __construct(MentalHealthSystemImport $parent)
    {
        $this->parent = $parent;
    }

    public function collection(Collection $collection)
    {
        Log::info("Procesando hoja TRASTORNOS 2025 - {$collection->count()} registros");

        foreach ($collection as $index => $row) {
            $this->processRow($row, $index + 2);
        }
    }

    private function processRow(Collection $row, int $rowNumber)
    {
        try {
            $documentNumber = $this->cleanString($row['n_documento']);
            
            if (empty($documentNumber) || !$this->isValidDocument($documentNumber)) {
                $this->parent->incrementSkipped();
                return;
            }

            // 1. Crear o actualizar paciente
            $patient = $this->createOrUpdatePatient($row, $rowNumber);
            if (!$patient) return;

            // 2. Crear caso de trastorno mental
            $mentalDisorder = $this->createMentalDisorderCase($patient, $row, $rowNumber);
            if (!$mentalDisorder) return;

            // 3. Crear seguimientos mensuales asociados al caso
            $this->processMonthlyFollowups($mentalDisorder, $row, $rowNumber);

        } catch (\Exception $e) {
            $this->parent->addError("TRASTORNOS Fila {$rowNumber}: " . $e->getMessage());
            Log::error("Error en TRASTORNOS fila {$rowNumber}: " . $e->getMessage());
        }
    }

    private function createOrUpdatePatient(Collection $row, int $rowNumber)
    {
        $documentNumber = $this->cleanString($row['n_documento']);
        $patient = Patient::where('document_number', $documentNumber)->first();

        $patientData = [
            'document_number' => $documentNumber,
            'document_type' => $this->mapDocumentType($row['tipo_de_documento']),
            'full_name' => $this->truncateString($this->cleanString($row['nombres_y_apellidos']), 255),
            'gender' => $this->mapGender($row['sex0']),
            'birth_date' => $this->parseDate($row['fecha_de_nacimiento'])?->format('Y-m-d'),
            'phone' => $this->cleanPhone($row['telefono']),
            'address' => $this->truncateString($this->cleanString($row['direccion']), 255),
            'village' => $this->truncateString($this->cleanString($row['vereda']), 255),
            'eps_code' => $this->truncateString($this->cleanString($row['eps_codigo']), 255),
            'eps_name' => $this->truncateString($this->cleanString($row['eps_nombre']), 255),
            'status' => 'active',
        ];

        $patientData = array_filter($patientData, fn($value) => $value !== null);

        try {
            if ($patient) {
                $patient->update($patientData);
                $this->parent->incrementUpdated();
            } else {
                $patient = Patient::create($patientData);
                $this->parent->incrementImported();
            }
            return $patient;
        } catch (\Exception $e) {
            $this->parent->addError("TRASTORNOS Fila {$rowNumber}: Error BD paciente - " . $e->getMessage());
            return null;
        }
    }

    private function createMentalDisorderCase(Patient $patient, Collection $row, int $rowNumber)
    {
        try {
            // Verificar si ya existe un caso para este paciente
            $existingCase = MentalDisorder::where('patient_id', $patient->id)->first();
            
            $caseData = [
                'patient_id' => $patient->id,
                'admission_date' => $this->parseDate($row['fecha_de_ingreso']) ?? now(),
                'admission_type' => $this->cleanString($row['tipo_de_ingreso']),
                'admission_via' => $this->cleanString($row['ingreso_por']),
                'service_area' => $this->cleanString($row['area_servicio_de_atencion']),
                'diagnosis_code' => $this->cleanString($row['diag_codigo']),
                'diagnosis_description' => $this->cleanString($row['diagnostico']),
                'diagnosis_date' => $this->parseDate($row['fecha_diagnostico']),
                'diagnosis_type' => $this->cleanString($row['tipo_diagnostico']),
                'additional_observation' => $this->cleanString($row['observacion_adicional']),
                'status' => 'active',
                'created_by' => auth()->id() ?? 1,
            ];

            $caseData = array_filter($caseData, fn($value) => $value !== null);

            if ($existingCase) {
                $existingCase->update($caseData);
                return $existingCase;
            } else {
                $mentalDisorder = MentalDisorder::create($caseData);
                $this->parent->incrementCases();
                return $mentalDisorder;
            }

        } catch (\Exception $e) {
            $this->parent->addError("TRASTORNOS Fila {$rowNumber}: Error creando caso - " . $e->getMessage());
            return null;
        }
    }

    private function processMonthlyFollowups($mentalDisorder, Collection $row, int $rowNumber)
    {
        $months = [
            'enero_2025' => 1, 'febrero_2025' => 2, 'marzo_2025' => 3, 'abril_2025' => 4,
            'mayo_2025' => 5, 'junio_2025' => 6, 'julio_2025' => 7, 'agosto_2025' => 8,
            'septiembre_2025' => 9, 'octubre_2025' => 10, 'noviembre_2025' => 11, 'diciembre_2025' => 12
        ];

        foreach ($months as $columnName => $monthNumber) {
            $followupData = $this->cleanString($row[$columnName]);
            
            if (empty($followupData) || strlen($followupData) < 2) continue;

            // Verificar si ya existe seguimiento para este mes
            $existingFollowup = MonthlyFollowup::where('followupable_id', $mentalDisorder->id)
                ->where('followupable_type', Patient::class)
                ->where('year', 2025)
                ->where('month', $monthNumber)
                ->first();

            if ($existingFollowup) continue;

            try {
                $description = "Seguimiento TRASTORNO MENTAL - " . substr($followupData, 0, 500);
                
                if (!empty($mentalDisorder->diagnosis_description)) {
                    $description .= " | Dx: " . substr($mentalDisorder->diagnosis_description, 0, 100);
                }

                MonthlyFollowup::create([
                    'followupable_id' => $mentalDisorder->id,
                    'followupable_type' => Patient::class,
                    'followup_date' => Carbon::create(2025, $monthNumber, 15)->format('Y-m-d'),
                    'year' => 2025,
                    'month' => $monthNumber,
                    'description' => substr($description, 0, 1000),
                    'status' => 'completed',
                    'actions_taken' => json_encode(['Seguimiento trastorno mental']),
                    'performed_by' => auth()->id() ?? 1,
                ]);

                $this->parent->incrementFollowups();

            } catch (\Exception $e) {
                $this->parent->addError("TRASTORNOS Fila {$rowNumber}: Error seguimiento mes {$monthNumber} - " . $e->getMessage());
            }
        }
    }

    // Métodos helper
    private function cleanString($value): ?string
    {
        if (empty($value)) return null;
        return trim(strip_tags((string)$value)) ?: null;
    }

    private function cleanPhone($phone): ?string
    {
        if (empty($phone)) return null;
        $phones = preg_split('/[-,\s\/]/', (string)$phone);
        $firstPhone = trim($phones[0]);
        $cleaned = preg_replace('/\D/', '', $firstPhone);
        return (strlen($cleaned) >= 7 && strlen($cleaned) <= 20) ? $cleaned : null;
    }

    private function truncateString($string, $maxLength): ?string
    {
        if (empty($string)) return null;
        return strlen($string) > $maxLength ? substr($string, 0, $maxLength) : $string;
    }

    private function isValidDocument($document): bool
    {
        $clean = preg_replace('/\D/', '', (string)$document);
        return !empty($clean) && strlen($clean) >= 6 && strlen($clean) <= 15;
    }

    private function mapDocumentType($value): string
    {
        if (empty($value)) return 'CC';
        $type = strtoupper(trim((string)$value));
        return in_array($type, ['CC', 'TI', 'CE', 'PA', 'RC', 'MS', 'AS', 'CN']) ? $type : 'CC';
    }

    private function mapGender($value): string
    {
        if (empty($value)) return 'Otro';
        $gender = strtoupper(trim((string)$value));
        if (in_array($gender, ['M', 'MASCULINO', 'HOMBRE'])) return 'Masculino';
        if (in_array($gender, ['F', 'FEMENINO', 'MUJER'])) return 'Femenino';
        return 'Otro';
    }

    private function parseDate($value): ?Carbon
    {
        if (empty($value)) return null;
        try {
            return is_numeric($value) 
                ? Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays($value)
                : Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }
}

// ==================== HOJA EVENTO 356 COMPLETA ====================
class Evento356Sheet implements ToCollection, WithHeadingRow
{
    protected $parent;

    public function __construct(MentalHealthSystemImport $parent)
    {
        $this->parent = $parent;
    }

    public function collection(Collection $collection)
    {
        Log::info("Procesando hoja EVENTO 356 2025 - {$collection->count()} registros");

        foreach ($collection as $index => $row) {
            $this->processRow($row, $index + 2);
        }
    }

    private function processRow(Collection $row, int $rowNumber)
    {
        try {
            $documentNumber = $this->cleanString($row['n_documento']);
            
            if (empty($documentNumber) || !$this->isValidDocument($documentNumber)) {
                $this->parent->incrementSkipped();
                return;
            }

            // 1. Crear o actualizar paciente
            $patient = $this->createOrUpdatePatient($row, $rowNumber);
            if (!$patient) return;

            // 2. Crear caso de intento de suicidio
            $suicideAttempt = $this->createSuicideAttemptCase($patient, $row, $rowNumber);
            if (!$suicideAttempt) return;

            // 3. Crear seguimientos mensuales asociados al caso
            $this->processMonthlyFollowups($suicideAttempt, $row, $rowNumber);

        } catch (\Exception $e) {
            $this->parent->addError("EVENTO 356 Fila {$rowNumber}: " . $e->getMessage());
        }
    }

    private function createOrUpdatePatient(Collection $row, int $rowNumber)
    {
        $documentNumber = $this->cleanString($row['n_documento']);
        $patient = Patient::where('document_number', $documentNumber)->first();

        $patientData = [
            'document_number' => $documentNumber,
            'document_type' => $this->mapDocumentType($row['tipo_doc']),
            'full_name' => $this->truncateString($this->cleanString($row['nombres_y_apellidos']), 255),
            'gender' => $this->mapGender($row['sexo']),
            'birth_date' => $this->parseDate($row['fecha_de_nacimiento'])?->format('Y-m-d'),
            'phone' => $this->cleanPhone($row['telefono']),
            'address' => $this->truncateString($this->cleanString($row['direccion']), 255),
            'neighborhood' => $this->truncateString($this->cleanString($row['barrio']), 255),
            'village' => $this->truncateString($this->cleanString($row['vereda']), 255),
            'status' => 'active',
        ];

        $patientData = array_filter($patientData, fn($value) => $value !== null);

        try {
            if ($patient) {
                $patient->update($patientData);
                $this->parent->incrementUpdated();
            } else {
                $patient = Patient::create($patientData);
                $this->parent->incrementImported();
            }
            return $patient;
        } catch (\Exception $e) {
            $this->parent->addError("EVENTO 356 Fila {$rowNumber}: Error BD paciente - " . $e->getMessage());
            return null;
        }
    }

    private function createSuicideAttemptCase(Patient $patient, Collection $row, int $rowNumber)
    {
        try {
            // Verificar si ya existe un caso para este paciente
            $existingCase = SuicideAttempt::where('patient_id', $patient->id)->first();

            $riskFactorsArray = [];
            $riskFactorsText = $this->cleanString($row['factores_de_riesgo']);
            if (!empty($riskFactorsText)) {
                $riskFactorsArray = array_map('trim', explode(',', $riskFactorsText));
            }

            $caseData = [
                'patient_id' => $patient->id,
                'event_date' => $this->parseDate($row['fecha_de_ingreso']) ?? now(),
                'week_number' => $this->cleanString($row['semana']),
                'admission_via' => $this->cleanString($row['ingreso_por']),
                'attempt_number' => (int)($this->cleanString($row['n_intentos']) ?? 1),
                'benefit_plan' => $this->cleanString($row['plan_de_beneficios']),
                'trigger_factor' => $this->cleanString($row['desencadenante']),
                'risk_factors' => $riskFactorsArray,
                'mechanism' => $this->cleanString($row['mecanismo']),
                'additional_observation' => $this->cleanString($row['observacion_adicional']),
                'status' => 'active',
                'created_by' => auth()->id() ?? 1,
            ];

            $caseData = array_filter($caseData, function($value) {
                return $value !== null && $value !== '' && $value !== [];
            });

            if ($existingCase) {
                $existingCase->update($caseData);
                return $existingCase;
            } else {
                $suicideAttempt = SuicideAttempt::create($caseData);
                $this->parent->incrementCases();
                return $suicideAttempt;
            }

        } catch (\Exception $e) {
            $this->parent->addError("EVENTO 356 Fila {$rowNumber}: Error creando caso - " . $e->getMessage());
            return null;
        }
    }

    private function processMonthlyFollowups($suicideAttempt, Collection $row, int $rowNumber)
    {
        $months = [
            'enero_2025' => 1, 'febrero_2025' => 2, 'marzo_2025' => 3, 'abril_2025' => 4,
            'mayo_2025' => 5, 'junio_2025' => 6, 'julio_2025' => 7, 'agosto_2025' => 8,
            'septiembre_2025' => 9, 'octubre_2025' => 10, 'noviembre_2025' => 11, 'diciembre_2025' => 12
        ];

        foreach ($months as $columnName => $monthNumber) {
            $followupData = $this->cleanString($row[$columnName]);
            
            if (empty($followupData) || strlen($followupData) < 2) continue;

            $existingFollowup = MonthlyFollowup::where('followupable_id', $suicideAttempt->id)
                ->where('followupable_type', Patient::class)
                ->where('year', 2025)
                ->where('month', $monthNumber)
                ->first();

            if ($existingFollowup) continue;

            try {
                $description = "Seguimiento INTENTO SUICIDIO - " . substr($followupData, 0, 500);
                
                $additionalInfo = [];
                if (!empty($suicideAttempt->attempt_number)) {
                    $additionalInfo[] = "Intento #{$suicideAttempt->attempt_number}";
                }
                if (!empty($suicideAttempt->mechanism)) {
                    $additionalInfo[] = "Mecanismo: {$suicideAttempt->mechanism}";
                }

                if (!empty($additionalInfo)) {
                    $description .= " | " . implode(" | ", $additionalInfo);
                }

                MonthlyFollowup::create([
                    'followupable_id' => $suicideAttempt->id,
                    'followupable_type' => Patient::class,
                    'followup_date' => Carbon::create(2025, $monthNumber, 15)->format('Y-m-d'),
                    'year' => 2025,
                    'month' => $monthNumber,
                    'description' => substr($description, 0, 1000),
                    'status' => 'completed',
                    'actions_taken' => json_encode(['Seguimiento intento suicidio']),
                    'performed_by' => auth()->id() ?? 1,
                ]);

                $this->parent->incrementFollowups();

            } catch (\Exception $e) {
                $this->parent->addError("EVENTO 356 Fila {$rowNumber}: Error seguimiento - " . $e->getMessage());
            }
        }
    }

    // Métodos helper (iguales que TrastornosSheet)
    private function cleanString($value): ?string { return empty($value) ? null : (trim(strip_tags((string)$value)) ?: null); }
    private function cleanPhone($phone): ?string { if (empty($phone)) return null; $phones = preg_split('/[-,\s\/]/', (string)$phone); $firstPhone = trim($phones[0]); $cleaned = preg_replace('/\D/', '', $firstPhone); return (strlen($cleaned) >= 7 && strlen($cleaned) <= 20) ? $cleaned : null; }
    private function truncateString($string, $maxLength): ?string { return empty($string) ? null : (strlen($string) > $maxLength ? substr($string, 0, $maxLength) : $string); }
    private function isValidDocument($document): bool { $clean = preg_replace('/\D/', '', (string)$document); return !empty($clean) && strlen($clean) >= 6 && strlen($clean) <= 15; }
    private function mapDocumentType($value): string { if (empty($value)) return 'CC'; $type = strtoupper(trim((string)$value)); return in_array($type, ['CC', 'TI', 'CE', 'PA', 'RC']) ? $type : 'CC'; }
    private function mapGender($value): string { if (empty($value)) return 'Otro'; $gender = strtoupper(trim((string)$value)); if (in_array($gender, ['M', 'MASCULINO', 'HOMBRE'])) return 'Masculino'; if (in_array($gender, ['F', 'FEMENINO', 'MUJER'])) return 'Femenino'; return 'Otro'; }
    private function parseDate($value): ?Carbon { if (empty($value)) return null; try { return is_numeric($value) ? Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays($value) : Carbon::parse($value); } catch (\Exception $e) { return null; } }
}

// ==================== HOJA CONSUMO SPA COMPLETA ====================
class ConsumoSpaSheet implements ToCollection, WithHeadingRow
{
    protected $parent;

    public function __construct(MentalHealthSystemImport $parent)
    {
        $this->parent = $parent;
    }

    public function collection(Collection $collection)
    {
        Log::info("Procesando hoja CONSUMO SPA 2025 - {$collection->count()} registros");

        foreach ($collection as $index => $row) {
            $this->processRow($row, $index + 2);
        }
    }

    private function processRow(Collection $row, int $rowNumber)
    {
        try {
            $documentNumber = $this->cleanString($row['n_documento']);
            
            if (empty($documentNumber) || !$this->isValidDocument($documentNumber)) {
                $this->parent->incrementSkipped();
                return;
            }

            // 1. Crear o actualizar paciente
            $patient = $this->createOrUpdatePatient($row, $rowNumber);
            if (!$patient) return;

            // 2. Crear caso de consumo de sustancias
            $substanceConsumption = $this->createSubstanceConsumptionCase($patient, $row, $rowNumber);
            if (!$substanceConsumption) return;

            // 3. Crear seguimientos mensuales asociados al caso
            $this->processMonthlyFollowups($substanceConsumption, $row, $rowNumber);

        } catch (\Exception $e) {
            $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: " . $e->getMessage());
        }
    }

    private function createOrUpdatePatient(Collection $row, int $rowNumber)
    {
        $documentNumber = $this->cleanString($row['n_documento']);
        $patient = Patient::where('document_number', $documentNumber)->first();

        $patientData = [
            'document_number' => $documentNumber,
            'document_type' => $this->mapDocumentType($row['tipo_doc']),
            'full_name' => $this->truncateString($this->cleanString($row['nombre_completo']), 255),
            'gender' => $this->mapGender($row['sexo']),
            'birth_date' => $this->parseDate($row['fecha_de_nacimiento'])?->format('Y-m-d'),
            'phone' => $this->cleanPhone($row['telefono']),
            'eps_name' => $this->truncateString($this->cleanString($row['eps'] ?? $row['nombre']), 255),
            'status' => 'active',
        ];

        $patientData = array_filter($patientData, fn($value) => $value !== null);

        try {
            if ($patient) {
                $patient->update($patientData);
                $this->parent->incrementUpdated();
            } else {
                $patient = Patient::create($patientData);
                $this->parent->incrementImported();
            }
            return $patient;
        } catch (\Exception $e) {
            $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: Error BD paciente - " . $e->getMessage());
            return null;
        }
    }

    private function createSubstanceConsumptionCase(Patient $patient, Collection $row, int $rowNumber)
    {
        try {
            // Verificar si ya existe un caso para este paciente
            $existingCase = SubstanceConsumption::where('patient_id', $patient->id)->first();

            // Procesar sustancias utilizadas
            $substancesArray = [];
            // En este caso no hay columna específica de sustancias en CONSUMO SPA, 
            // pero podemos inferirlo del diagnóstico o crear un array genérico
            $substancesArray = ['SPA']; // Genérico por ahora

            $caseData = [
                'patient_id' => $patient->id,
                'admission_date' => $this->parseDate($row['fecha_de_ingres']) ?? now(),
                'admission_via' => $this->cleanString($row['ingreso_por']),
                'diagnosis' => $this->cleanString($row['diagnostico']),
                'substances_used' => $substancesArray,
                'consumption_level' => 'No especificado', // No está en el Excel
                'additional_observation' => $this->cleanString($row['observacion_adicional']),
                'status' => 'active',
                'created_by' => auth()->id() ?? 1,
            ];

            $caseData = array_filter($caseData, function($value) {
                return $value !== null && $value !== '' && $value !== [];
            });

            if ($existingCase) {
                $existingCase->update($caseData);
                return $existingCase;
            } else {
                $substanceConsumption = SubstanceConsumption::create($caseData);
                $this->parent->incrementCases();
                return $substanceConsumption;
            }

        } catch (\Exception $e) {
            $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: Error creando caso - " . $e->getMessage());
            return null;
        }
    }

    private function processMonthlyFollowups($substanceConsumption, Collection $row, int $rowNumber)
    {
        $months = [
            'enero_2025' => 1, 'febrero_2025' => 2, 'marzo_2025' => 3, 'abril_2025' => 4,
            'mayo_2025' => 5, 'junio_2025' => 6, 'julio_2025' => 7, 'agosto_2025' => 8,
            'septiembre_2025' => 9, 'octubre_2025' => 10, 'noviembre_2025' => 11, 'diciembre_2025' => 12
        ];

        foreach ($months as $columnName => $monthNumber) {
            $followupData = $this->cleanString($row[$columnName]);
            
            if (empty($followupData) || strlen($followupData) < 2) continue;

            $existingFollowup = MonthlyFollowup::where('followupable_id', $substanceConsumption->id)
                ->where('followupable_type', Patient::class)
                ->where('year', 2025)
                ->where('month', $monthNumber)
                ->first();

            if ($existingFollowup) continue;

            try {
                $description = "Seguimiento CONSUMO SPA - " . substr($followupData, 0, 500);
                
                if (!empty($substanceConsumption->diagnosis)) {
                    $description .= " | Dx: " . substr($substanceConsumption->diagnosis, 0, 100);
                }

                MonthlyFollowup::create([
                    'followupable_id' => $substanceConsumption->id,
                    'followupable_type' => Patient::class,
                    'followup_date' => Carbon::create(2025, $monthNumber, 15)->format('Y-m-d'),
                    'year' => 2025,
                    'month' => $monthNumber,
                    'description' => substr($description, 0, 1000),
                    'status' => 'completed',
                    'actions_taken' => json_encode(['Seguimiento consumo SPA']),
                    'performed_by' => auth()->id() ?? 1,
                ]);

                $this->parent->incrementFollowups();

            } catch (\Exception $e) {
                $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: Error seguimiento - " . $e->getMessage());
            }
        }
    }

    // Métodos helper (iguales que las otras hojas)
    private function cleanString($value): ?string { return empty($value) ? null : (trim(strip_tags((string)$value)) ?: null); }
    private function cleanPhone($phone): ?string { if (empty($phone)) return null; $phones = preg_split('/[-,\s\/]/', (string)$phone); $firstPhone = trim($phones[0]); $cleaned = preg_replace('/\D/', '', $firstPhone); return (strlen($cleaned) >= 7 && strlen($cleaned) <= 20) ? $cleaned : null; }
    private function truncateString($string, $maxLength): ?string { return empty($string) ? null : (strlen($string) > $maxLength ? substr($string, 0, $maxLength) : $string); }
    private function isValidDocument($document): bool { $clean = preg_replace('/\D/', '', (string)$document); return !empty($clean) && strlen($clean) >= 6 && strlen($clean) <= 15; }
    private function mapDocumentType($value): string { if (empty($value)) return 'CC'; $type = strtoupper(trim((string)$value)); return in_array($type, ['CC', 'TI', 'CE', 'PA', 'RC']) ? $type : 'CC'; }
    private function mapGender($value): string { if (empty($value)) return 'Otro'; $gender = strtoupper(trim((string)$value)); if (in_array($gender, ['M', 'MASCULINO', 'HOMBRE'])) return 'Masculino'; if (in_array($gender, ['F', 'FEMENINO', 'MUJER'])) return 'Femenino'; return 'Otro'; }
    private function parseDate($value): ?Carbon { if (empty($value)) return null; try { return is_numeric($value) ? Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays($value) : Carbon::parse($value); } catch (\Exception $e) { return null; } }
}