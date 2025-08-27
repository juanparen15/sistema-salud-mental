<?php

namespace App\Imports;

use App\Models\Patient;
use App\Models\MonthlyFollowup;
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

    public function sheets(): array
    {
        return [
            'TRASTORNOS 2025' => new TrastornosSheet($this),
            'EVENTO 356 2025' => new Evento356Sheet($this),
            'CONSUMO SPA 2025' => new ConsumoSpaSheet($this),
        ];
    }

    // Métodos para actualizar contadores desde las hojas
    public function incrementImported() { $this->importedCount++; }
    public function incrementUpdated() { $this->updatedCount++; }
    public function incrementSkipped() { $this->skippedCount++; }
    public function incrementFollowups() { $this->followupsCreated++; }
    public function addError($error) { $this->errors[] = $error; }

    // Getters para estadísticas
    public function getImportedCount(): int { return $this->importedCount; }
    public function getUpdatedCount(): int { return $this->updatedCount; }
    public function getSkippedCount(): int { return $this->skippedCount; }
    public function getFollowupsCreated(): int { return $this->followupsCreated; }
    public function getErrors(): array { return $this->errors; }
}

// ==================== HOJA TRASTORNOS ====================
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
            $this->processRow($row, $index + 2, 'trastorno');
        }
    }

    private function processRow(Collection $row, int $rowNumber, string $eventType)
    {
        try {
            // Extraer datos básicos del paciente
            $documentNumber = $this->cleanString($row['n_documento']);
            
            if (empty($documentNumber)) {
                $this->parent->incrementSkipped();
                $this->parent->addError("TRASTORNOS Fila {$rowNumber}: Sin número de documento");
                return;
            }

            $fullName = $this->cleanString($row['nombres_y_apellidos']);
            if (empty($fullName)) {
                $this->parent->incrementSkipped();
                $this->parent->addError("TRASTORNOS Fila {$rowNumber}: Sin nombre (doc: {$documentNumber})");
                return;
            }

            // Crear o actualizar paciente
            $patient = $this->createOrUpdatePatient($row, $rowNumber, $eventType);
            if (!$patient) return;

            // Procesar seguimientos mensuales
            $this->processMonthlyFollowups($patient, $row, $rowNumber, $eventType);

        } catch (\Exception $e) {
            $this->parent->addError("TRASTORNOS Fila {$rowNumber}: " . $e->getMessage());
            Log::error("Error en TRASTORNOS fila {$rowNumber}: " . $e->getMessage());
        }
    }

    private function createOrUpdatePatient(Collection $row, int $rowNumber, string $eventType)
    {
        $documentNumber = $this->cleanString($row['n_documento']);
        $patient = Patient::where('document_number', $documentNumber)->first();

        $patientData = [
            'document_number' => $documentNumber,
            'document_type' => $this->mapDocumentType($row['tipo_de_documento']),
            'full_name' => $this->cleanString($row['nombres_y_apellidos']),
            'gender' => $this->mapGender($row['sex0']),
            'birth_date' => $this->parseDate($row['fecha_de_nacimiento'])?->format('Y-m-d'),
            'phone' => $this->cleanString($row['telefono']),
            'address' => $this->cleanString($row['direccion']),
            'village' => $this->cleanString($row['vereda']),
            'eps_code' => $this->cleanString($row['eps_codigo']),
            'eps_name' => $this->cleanString($row['eps_nombre']),
            'status' => 'active',
        ];

        // Filtrar valores nulos
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
            $this->parent->addError("TRASTORNOS Fila {$rowNumber}: Error creando paciente - " . $e->getMessage());
            return null;
        }
    }

    private function processMonthlyFollowups(Patient $patient, Collection $row, int $rowNumber, string $eventType)
    {
        $months = [
            'enero_2025' => 1, 'febrero_2025' => 2, 'marzo_2025' => 3, 'abril_2025' => 4,
            'mayo_2025' => 5, 'junio_2025' => 6, 'julio_2025' => 7, 'agosto_2025' => 8,
            'septiembre_2025' => 9, 'octubre_2025' => 10, 'noviembre_2025' => 11, 'diciembre_2025' => 12
        ];

        foreach ($months as $columnName => $monthNumber) {
            $followupData = $this->cleanString($row[$columnName]);
            
            if (empty($followupData)) continue;

            // Verificar si ya existe seguimiento para este mes
            $existingFollowup = MonthlyFollowup::where('followupable_id', $patient->id)
                ->where('followupable_type', Patient::class)
                ->where('year', 2025)
                ->where('month', $monthNumber)
                ->first();

            if ($existingFollowup) continue; // Ya existe

            // Crear seguimiento
            $description = "Seguimiento TRASTORNO - " . $followupData;
            
            // Agregar información específica de trastornos
            if (!empty($row['diagnostico'])) {
                $description .= " | Diagnóstico: " . $this->cleanString($row['diagnostico']);
            }
            if (!empty($row['observacion_adicional'])) {
                $description .= " | Obs: " . $this->cleanString($row['observacion_adicional']);
            }

            MonthlyFollowup::create([
                'followupable_id' => $patient->id,
                'followupable_type' => Patient::class,
                'followup_date' => Carbon::create(2025, $monthNumber, 15)->format('Y-m-d'), // Día 15 del mes
                'year' => 2025,
                'month' => $monthNumber,
                'description' => $description,
                'status' => 'completed',
                'actions_taken' => json_encode(['Seguimiento de trastorno mental']),
                'performed_by' => auth()->id() ?? 1,
            ]);

            $this->parent->incrementFollowups();
        }
    }

    private function cleanString($value): ?string
    {
        return empty($value) ? null : trim(strip_tags((string) $value));
    }

    private function mapDocumentType($value): string
    {
        if (empty($value)) return 'CC';
        $type = strtoupper(trim($value));
        return in_array($type, ['CC', 'TI', 'CE', 'PA', 'RC']) ? $type : 'CC';
    }

    private function mapGender($value): string
    {
        if (empty($value)) return 'Otro';
        $gender = strtoupper(trim($value));
        if (in_array($gender, ['M', 'MASCULINO'])) return 'Masculino';
        if (in_array($gender, ['F', 'FEMENINO'])) return 'Femenino';
        return 'Otro';
    }

    private function parseDate($value): ?Carbon
    {
        if (empty($value)) return null;
        try {
            if (is_numeric($value)) {
                return Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays($value);
            }
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }
}

// ==================== HOJA EVENTO 356 ====================
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
            $this->processRow($row, $index + 2, 'intento_suicidio');
        }
    }

    private function processRow(Collection $row, int $rowNumber, string $eventType)
    {
        try {
            $documentNumber = $this->cleanString($row['n_documento']);
            
            if (empty($documentNumber)) {
                $this->parent->incrementSkipped();
                $this->parent->addError("EVENTO 356 Fila {$rowNumber}: Sin número de documento");
                return;
            }

            $patient = $this->createOrUpdatePatient($row, $rowNumber, $eventType);
            if (!$patient) return;

            $this->processMonthlyFollowups($patient, $row, $rowNumber, $eventType);

        } catch (\Exception $e) {
            $this->parent->addError("EVENTO 356 Fila {$rowNumber}: " . $e->getMessage());
        }
    }

    private function createOrUpdatePatient(Collection $row, int $rowNumber, string $eventType)
    {
        $documentNumber = $this->cleanString($row['n_documento']);
        $patient = Patient::where('document_number', $documentNumber)->first();

        $patientData = [
            'document_number' => $documentNumber,
            'document_type' => $this->mapDocumentType($row['tipo_doc']),
            'full_name' => $this->cleanString($row['nombres_y_apellidos']),
            'gender' => $this->mapGender($row['sexo']),
            'birth_date' => $this->parseDate($row['fecha_de_nacimiento'])?->format('Y-m-d'),
            'phone' => $this->cleanString($row['telefono']),
            'address' => $this->cleanString($row['direccion']),
            'neighborhood' => $this->cleanString($row['barrio']),
            'village' => $this->cleanString($row['vereda']),
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
            $this->parent->addError("EVENTO 356 Fila {$rowNumber}: Error creando paciente - " . $e->getMessage());
            return null;
        }
    }

    private function processMonthlyFollowups(Patient $patient, Collection $row, int $rowNumber, string $eventType)
    {
        $months = [
            'enero_2025' => 1, 'febrero_2025' => 2, 'marzo_2025' => 3, 'abril_2025' => 4,
            'mayo_2025' => 5, 'junio_2025' => 6, 'julio_2025' => 7, 'agosto_2025' => 8,
            'septiembre_2025' => 9, 'octubre_2025' => 10, 'noviembre_2025' => 11, 'diciembre_2025' => 12
        ];

        foreach ($months as $columnName => $monthNumber) {
            $followupData = $this->cleanString($row[$columnName]);
            
            if (empty($followupData)) continue;

            $existingFollowup = MonthlyFollowup::where('followupable_id', $patient->id)
                ->where('followupable_type', Patient::class)
                ->where('year', 2025)
                ->where('month', $monthNumber)
                ->first();

            if ($existingFollowup) continue;

            // Crear descripción específica para intento de suicidio
            $description = "Seguimiento INTENTO SUICIDIO - " . $followupData;
            
            if (!empty($row['n_intentos'])) {
                $description .= " | N° Intentos: " . $this->cleanString($row['n_intentos']);
            }
            if (!empty($row['desencadenante'])) {
                $description .= " | Desencadenante: " . $this->cleanString($row['desencadenante']);
            }
            if (!empty($row['mecanismo'])) {
                $description .= " | Mecanismo: " . $this->cleanString($row['mecanismo']);
            }

            $actions = ['Seguimiento intento suicidio'];
            if (!empty($row['factores_de_riesgo'])) {
                $actions[] = 'Evaluación factores de riesgo: ' . $this->cleanString($row['factores_de_riesgo']);
            }

            MonthlyFollowup::create([
                'followupable_id' => $patient->id,
                'followupable_type' => Patient::class,
                'followup_date' => Carbon::create(2025, $monthNumber, 15)->format('Y-m-d'),
                'year' => 2025,
                'month' => $monthNumber,
                'description' => $description,
                'status' => 'completed',
                'actions_taken' => json_encode($actions),
                'performed_by' => auth()->id() ?? 1,
            ]);

            $this->parent->incrementFollowups();
        }
    }

    // Métodos helper reutilizados
    private function cleanString($value): ?string { return empty($value) ? null : trim(strip_tags((string) $value)); }
    private function mapDocumentType($value): string { return empty($value) ? 'CC' : (in_array(strtoupper(trim($value)), ['CC', 'TI', 'CE', 'PA', 'RC']) ? strtoupper(trim($value)) : 'CC'); }
    private function mapGender($value): string { 
        if (empty($value)) return 'Otro';
        $gender = strtoupper(trim($value));
        if (in_array($gender, ['M', 'MASCULINO'])) return 'Masculino';
        if (in_array($gender, ['F', 'FEMENINO'])) return 'Femenino';
        return 'Otro';
    }
    private function parseDate($value): ?Carbon { 
        if (empty($value)) return null;
        try { return is_numeric($value) ? Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays($value) : Carbon::parse($value); } 
        catch (\Exception $e) { return null; }
    }
}

// ==================== HOJA CONSUMO SPA ====================
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
            $this->processRow($row, $index + 2, 'consumo_spa');
        }
    }

    private function processRow(Collection $row, int $rowNumber, string $eventType)
    {
        try {
            $documentNumber = $this->cleanString($row['n_documento']);
            
            if (empty($documentNumber)) {
                $this->parent->incrementSkipped();
                $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: Sin número de documento");
                return;
            }

            $patient = $this->createOrUpdatePatient($row, $rowNumber, $eventType);
            if (!$patient) return;

            $this->processMonthlyFollowups($patient, $row, $rowNumber, $eventType);

        } catch (\Exception $e) {
            $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: " . $e->getMessage());
        }
    }

    private function createOrUpdatePatient(Collection $row, int $rowNumber, string $eventType)
    {
        $documentNumber = $this->cleanString($row['n_documento']);
        $patient = Patient::where('document_number', $documentNumber)->first();

        $patientData = [
            'document_number' => $documentNumber,
            'document_type' => $this->mapDocumentType($row['tipo_doc']),
            'full_name' => $this->cleanString($row['nombre_completo']),
            'gender' => $this->mapGender($row['sexo']),
            'birth_date' => $this->parseDate($row['fecha_de_nacimiento'])?->format('Y-m-d'),
            'phone' => $this->cleanString($row['telefono']),
            'eps_name' => $this->cleanString($row['eps'] ?? $row['nombre']),
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
            $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: Error creando paciente - " . $e->getMessage());
            return null;
        }
    }

    private function processMonthlyFollowups(Patient $patient, Collection $row, int $rowNumber, string $eventType)
    {
        $months = [
            'enero_2025' => 1, 'febrero_2025' => 2, 'marzo_2025' => 3, 'abril_2025' => 4,
            'mayo_2025' => 5, 'junio_2025' => 6, 'julio_2025' => 7, 'agosto_2025' => 8,
            'septiembre_2025' => 9, 'octubre_2025' => 10, 'noviembre_2025' => 11, 'diciembre_2025' => 12
        ];

        foreach ($months as $columnName => $monthNumber) {
            $followupData = $this->cleanString($row[$columnName]);
            
            if (empty($followupData)) continue;

            $existingFollowup = MonthlyFollowup::where('followupable_id', $patient->id)
                ->where('followupable_type', Patient::class)
                ->where('year', 2025)
                ->where('month', $monthNumber)
                ->first();

            if ($existingFollowup) continue;

            $description = "Seguimiento CONSUMO SPA - " . $followupData;
            
            if (!empty($row['diagnostico'])) {
                $description .= " | Diagnóstico: " . $this->cleanString($row['diagnostico']);
            }
            if (!empty($row['observacion_adicional'])) {
                $description .= " | Obs: " . $this->cleanString($row['observacion_adicional']);
            }

            MonthlyFollowup::create([
                'followupable_id' => $patient->id,
                'followupable_type' => Patient::class,
                'followup_date' => Carbon::create(2025, $monthNumber, 15)->format('Y-m-d'),
                'year' => 2025,
                'month' => $monthNumber,
                'description' => $description,
                'status' => 'completed',
                'actions_taken' => json_encode(['Seguimiento consumo SPA']),
                'performed_by' => auth()->id() ?? 1,
            ]);

            $this->parent->incrementFollowups();
        }
    }

    // Métodos helper
    private function cleanString($value): ?string { return empty($value) ? null : trim(strip_tags((string) $value)); }
    private function mapDocumentType($value): string { return empty($value) ? 'CC' : (in_array(strtoupper(trim($value)), ['CC', 'TI', 'CE', 'PA', 'RC']) ? strtoupper(trim($value)) : 'CC'); }
    private function mapGender($value): string { 
        if (empty($value)) return 'Otro';
        $gender = strtoupper(trim($value));
        if (in_array($gender, ['M', 'MASCULINO'])) return 'Masculino';
        if (in_array($gender, ['F', 'FEMENINO'])) return 'Femenino';
        return 'Otro';
    }
    private function parseDate($value): ?Carbon { 
        if (empty($value)) return null;
        try { return is_numeric($value) ? Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays($value) : Carbon::parse($value); } 
        catch (\Exception $e) { return null; }
    }
}