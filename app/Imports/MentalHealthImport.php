<?php

namespace App\Imports;

use App\Models\Patient;
use App\Models\MentalDisorder;
use App\Models\SuicideAttempt;
use App\Models\SubstanceConsumption;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MentalHealthImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'TRASTORNOS 2025' => new MentalDisorderImport(),
            'EVENTO 356 2025' => new SuicideAttemptImport(),
            'CONSUMO SPA 2025' => new SubstanceConsumptionImport(),
        ];
    }
}

class MentalDisorderImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        
        try {
            foreach ($rows as $row) {
                if (empty($row['n_documento'])) continue;
                
                // Buscar o crear paciente
                $patient = Patient::firstOrCreate(
                    ['document_number' => $row['n_documento']],
                    [
                        'document_type' => $this->mapDocumentType($row['tipo_de_documento']),
                        'full_name' => $row['nombres_y_apellidos'],
                        'gender' => $this->mapGender($row['sex0']),
                        'birth_date' => $this->parseDate($row['fecha_de_nacimiento']),
                        'phone' => $row['telefono'],
                        'address' => $row['direccion'],
                        'neighborhood' => $row['barrio'] ?? null,
                        'village' => $row['vereda'],
                        'eps_code' => $row['eps_codigo'],
                        'eps_name' => $row['eps_nombre'],
                    ]
                );
                
                // Crear registro de trastorno mental
                MentalDisorder::create([
                    'patient_id' => $patient->id,
                    'admission_date' => $this->parseDate($row['fecha_de_ingreso']),
                    'admission_type' => strtoupper($row['tipo_de_ingreso'] ?? 'AMBULATORIO'),
                    'admission_via' => $this->mapAdmissionVia($row['ingreso_por']),
                    'service_area' => $row['area_servicio_de_atencion'],
                    'diagnosis_code' => $row['diag_codigo'],
                    'diagnosis_description' => $row['diagnostico'],
                    'diagnosis_date' => $this->parseDate($row['fecha_diagnostico']),
                    'diagnosis_type' => $row['tipo_diagnostico'] ?? 'Diagnostico Principal',
                    'additional_observation' => $row['observacion_adicional'],
                    'status' => 'active',
                ]);
                
                // Procesar seguimientos mensuales si existen
                $this->processMonthlyFollowups($patient, $row);
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importando trastornos mentales: ' . $e->getMessage());
            throw $e;
        }
    }
    
    protected function processMonthlyFollowups($patient, $row)
    {
        $months = [
            'enero_2025' => ['month' => 1, 'year' => 2025],
            'febrero_2025' => ['month' => 2, 'year' => 2025],
            'marzo_2025' => ['month' => 3, 'year' => 2025],
            'abril_2025' => ['month' => 4, 'year' => 2025],
            'mayo_2025' => ['month' => 5, 'year' => 2025],
            'junio_2025' => ['month' => 6, 'year' => 2025],
            'julio_2025' => ['month' => 7, 'year' => 2025],
            'agosto_2025' => ['month' => 8, 'year' => 2025],
            'septiembre_2025' => ['month' => 9, 'year' => 2025],
            'octubre_2025' => ['month' => 10, 'year' => 2025],
            'noviembre_2025' => ['month' => 11, 'year' => 2025],
            'diciembre_2025' => ['month' => 12, 'year' => 2025],
        ];
        
        foreach ($months as $key => $monthData) {
            if (!empty($row[$key])) {
                $disorder = $patient->mentalDisorders()->latest()->first();
                if ($disorder) {
                    $disorder->followups()->create([
                        'followup_date' => Carbon::create($monthData['year'], $monthData['month'], 15),
                        'year' => $monthData['year'],
                        'month' => $monthData['month'],
                        'description' => $row[$key],
                        'status' => 'completed',
                    ]);
                }
            }
        }
    }
    
    protected function mapDocumentType($type): string
    {
        $mapping = [
            'Cédula_Ciudadanía' => 'CC',
            'Tarjeta_de_Identidad' => 'TI',
            'Cédula_Extranjería' => 'CE',
            'Pasaporte' => 'PA',
            'Registro_Civil' => 'RC',
        ];
        
        return $mapping[$type] ?? 'CC';
    }
    
    protected function mapGender($gender): string
    {
        $mapping = [
            'M' => 'Masculino',
            'F' => 'Femenino',
            'Masculino' => 'Masculino',
            'Femenino' => 'Femenino',
        ];
        
        return $mapping[$gender] ?? 'Otro';
    }
    
    protected function mapAdmissionVia($via): string
    {
        $mapping = [
            'URGENCIAS' => 'URGENCIAS',
            'CONSULTA_EXTERNA' => 'CONSULTA_EXTERNA',
            'HOSPITALIZACION' => 'HOSPITALIZACION',
            'REFERENCIA' => 'REFERENCIA',
        ];
        
        return $mapping[strtoupper($via)] ?? 'URGENCIAS';
    }
    
    protected function parseDate($date)
    {
        if (empty($date)) return now();
        
        try {
            if (is_numeric($date)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date));
            }
            return Carbon::parse($date);
        } catch (\Exception $e) {
            return now();
        }
    }
    
    public function headingRow(): int
    {
        return 2; // La segunda fila contiene los encabezados
    }
}

class SuicideAttemptImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        
        try {
            foreach ($rows as $row) {
                if (empty($row['n_documento'])) continue;
                
                $patient = Patient::firstOrCreate(
                    ['document_number' => $row['n_documento']],
                    [
                        'document_type' => $row['tipo_doc'] ?? 'CC',
                        'full_name' => $row['nombres_y_apellidos'],
                        'gender' => $row['sexo'] === 'M' ? 'Masculino' : 'Femenino',
                        'birth_date' => $this->parseDate($row['fecha_de_nacimiento']),
                        'phone' => $row['telefono'],
                        'address' => $row['direccion'],
                        'neighborhood' => $row['barrio'],
                        'village' => $row['vereda'],
                    ]
                );
                
                SuicideAttempt::create([
                    'patient_id' => $patient->id,
                    'event_date' => $this->parseDate($row['fecha_de_ingreso']),
                    'week_number' => $row['semana'] ?? null,
                    'admission_via' => strtoupper($row['ingreso_por'] ?? 'URGENCIAS'),
                    'attempt_number' => $row['n_intentos'] ?? 1,
                    'benefit_plan' => $row['plan_de_beneficios'],
                    'trigger_factor' => $row['desencadenante'] ?? 'No especificado',
                    'risk_factors' => explode(',', $row['factores_de_riesgo'] ?? ''),
                    'mechanism' => $row['mecanismo'] ?? 'No especificado',
                    'additional_observation' => $row['observacion_adicional'],
                    'status' => 'active',
                ]);
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importando intentos de suicidio: ' . $e->getMessage());
            throw $e;
        }
    }
    
    protected function parseDate($date)
    {
        if (empty($date)) return now();
        
        try {
            if (is_numeric($date)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date));
            }
            return Carbon::parse($date);
        } catch (\Exception $e) {
            return now();
        }
    }
}

class SubstanceConsumptionImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        
        try {
            foreach ($rows as $row) {
                if (empty($row['n_documento'])) continue;
                
                $patient = Patient::firstOrCreate(
                    ['document_number' => $row['n_documento']],
                    [
                        'document_type' => $row['tipo_doc'] ?? 'CC',
                        'full_name' => $row['nombre_completo'],
                        'gender' => $row['sexo'] ?? 'Otro',
                        'birth_date' => $this->parseDate($row['fecha_de_nacimiento']),
                        'phone' => $row['telefono'],
                        'eps_name' => $row['eps_nombre'],
                    ]
                );
                
                SubstanceConsumption::create([
                    'patient_id' => $patient->id,
                    'admission_date' => $this->parseDate($row['fecha_de_ingres']),
                    'admission_via' => strtoupper($row['ingreso_por'] ?? 'URGENCIAS'),
                    'diagnosis' => $row['diagnostico'] ?? 'Consumo de sustancias psicoactivas',
                    'substances_used' => $this->parseSubstances($row['diagnostico']),
                    'consumption_level' => $this->determineConsumptionLevel($row['diagnostico']),
                    'additional_observation' => $row['observacion_adicional'],
                    'status' => 'active',
                ]);
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importando consumo SPA: ' . $e->getMessage());
            throw $e;
        }
    }
    
    protected function parseSubstances($diagnosis): array
    {
        $substances = [];
        
        $keywords = [
            'alcohol' => 'Alcohol',
            'marihuana' => 'Marihuana',
            'cocaina' => 'Cocaína',
            'basuco' => 'Basuco',
            'heroina' => 'Heroína',
            'multiples drogas' => 'Múltiples drogas',
        ];
        
        foreach ($keywords as $keyword => $substance) {
            if (stripos($diagnosis, $keyword) !== false) {
                $substances[] = $substance;
            }
        }
        
        return empty($substances) ? ['No especificado'] : $substances;
    }
    
    protected function determineConsumptionLevel($diagnosis): string
    {
        if (stripos($diagnosis, 'psicotico') !== false || stripos($diagnosis, 'grave') !== false) {
            return 'Alto Riesgo';
        } elseif (stripos($diagnosis, 'moderado') !== false) {
            return 'Riesgo Moderado';
        } elseif (stripos($diagnosis, 'leve') !== false) {
            return 'Bajo Riesgo';
        }
        
        return 'Perjudicial';
    }
    
    protected function parseDate($date)
    {
        if (empty($date)) return now();
        
        try {
            if (is_numeric($date)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date));
            }
            return Carbon::parse($date);
        } catch (\Exception $e) {
            return now();
        }
    }
}