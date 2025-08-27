<?php

// namespace App\Imports;

// use App\Models\Patient;
// use App\Models\MonthlyFollowup;
// use Illuminate\Support\Collection;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
// use Maatwebsite\Excel\Concerns\ToCollection;
// use Maatwebsite\Excel\Concerns\WithHeadingRow;
// use Maatwebsite\Excel\Concerns\WithValidation;
// use Carbon\Carbon;

// class PatientsImport implements ToCollection, WithHeadingRow, WithValidation
// {
//     protected $importedCount = 0;
//     protected $updatedCount = 0;
//     protected $skippedCount = 0;
//     protected $errors = [];

//     public function collection(Collection $collection)
//     {
//         DB::beginTransaction();

//         try {
//             foreach ($collection as $row) {
//                 $this->processRow($row);
//             }

//             DB::commit();

//         } catch (\Exception $e) {
//             DB::rollback();
//             Log::error('Error en importación masiva: ' . $e->getMessage());
//             throw $e;
//         }
//     }

//     private function processRow(Collection $row)
//     {
//         // Limpiar y validar datos
//         $identificationNumber = $this->cleanString($row['numero_identificacion'] ?? $row['identificacion']);

//         if (empty($identificationNumber)) {
//             $this->skippedCount++;
//             $this->errors[] = "Fila sin número de identificación saltada";
//             return;
//         }

//         // Buscar si el paciente ya existe
//         $patient = Patient::where('identification_number', $identificationNumber)->first();

//         $patientData = $this->mapPatientData($row);

//         if ($patient) {
//             // Actualizar datos del paciente existente
//             $patient->update($patientData);
//             $this->updatedCount++;
//         } else {
//             // Crear nuevo paciente
//             $patient = Patient::create($patientData);
//             $this->importedCount++;
//         }

//         // Procesar seguimiento mensual si existe información
//         $this->processFollowup($patient, $row);
//     }

//     private function mapPatientData(Collection $row): array
//     {
//         return [
//             'identification_number' => $this->cleanString($row['numero_identificacion'] ?? $row['identificacion']),
//             'first_name' => $this->cleanString($row['nombres'] ?? $row['primer_nombre']),
//             'last_name' => $this->cleanString($row['apellidos'] ?? $row['primer_apellido']),
//             'age' => $this->cleanNumber($row['edad']),
//             'gender' => $this->mapGender($row['genero'] ?? $row['sexo']),
//             'phone' => $this->cleanString($row['telefono'] ?? $row['celular']),
//             'email' => $this->cleanEmail($row['email'] ?? $row['correo']),
//             'address' => $this->cleanString($row['direccion']),
//             'municipality' => $this->cleanString($row['municipio']),
//             'emergency_contact' => $this->cleanString($row['contacto_emergencia']),
//             'emergency_phone' => $this->cleanString($row['telefono_emergencia']),
//         ];
//     }

//     private function processFollowup(Patient $patient, Collection $row)
//     {
//         // Verificar si hay información de seguimiento en la fila
//         $followDate = $this->parseDate($row['fecha_seguimiento'] ?? null);

//         if (!$followDate) {
//             return; // No hay información de seguimiento
//         }

//         // Verificar si ya existe un seguimiento para esta fecha
//         $existingFollowup = MonthlyFollowup::where('patient_id', $patient->id)
//             ->whereDate('follow_date', $followDate)
//             ->first();

//         if ($existingFollowup) {
//             return; // Ya existe seguimiento para esta fecha
//         }

//         $followupData = [
//             'patient_id' => $patient->id,
//             'user_id' => auth()->id() ?? 1, // Usuario por defecto si no hay autenticación
//             'follow_date' => $followDate,
//             'mood_state' => $this->mapMoodState($row['estado_animo'] ?? 'Regular'),
//             'suicide_risk' => $this->parseBoolean($row['riesgo_suicidio'] ?? false),
//             'suicide_attempt' => $this->parseBoolean($row['intento_suicidio'] ?? false),
//             'suicide_attempt_date' => $this->parseDate($row['fecha_intento'] ?? null),
//             'suicide_method' => $this->cleanString($row['metodo_intento'] ?? null),
//             'substance_use' => $this->parseBoolean($row['consumo_sustancias'] ?? false),
//             'substance_type' => $this->cleanString($row['tipo_sustancia'] ?? null),
//             'consumption_frequency' => $this->cleanString($row['frecuencia_consumo'] ?? null),
//             'consumption_duration' => $this->cleanString($row['duracion_consumo'] ?? null),
//             'impact_level' => $this->cleanString($row['nivel_impacto'] ?? null),
//             'violence_risk' => $this->parseBoolean($row['riesgo_violencia'] ?? false),
//             'intervention_provided' => $this->parseBoolean($row['intervencion_realizada'] ?? false),
//             'referral_made' => $this->parseBoolean($row['remision_realizada'] ?? false),
//             'referral_institution' => $this->cleanString($row['institucion_remision'] ?? null),
//             'next_appointment' => $this->parseDate($row['proxima_cita'] ?? null),
//             'observations' => $this->cleanString($row['observaciones'] ?? null),
//         ];

//         MonthlyFollowup::create($followupData);
//     }

//     // Métodos de limpieza y mapeo
//     private function cleanString($value): ?string
//     {
//         if (empty($value)) return null;
//         return trim(strip_tags($value));
//     }

//     private function cleanNumber($value): ?int
//     {
//         if (empty($value)) return null;
//         return (int) preg_replace('/[^0-9]/', '', $value);
//     }

//     private function cleanEmail($value): ?string
//     {
//         if (empty($value)) return null;
//         $email = trim(strtolower($value));
//         return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
//     }

//     private function mapGender($value): string
//     {
//         if (empty($value)) return 'Otro';

//         $gender = strtoupper(trim($value));

//         if (in_array($gender, ['M', 'MASCULINO', 'HOMBRE', 'MALE'])) {
//             return 'M';
//         } elseif (in_array($gender, ['F', 'FEMENINO', 'MUJER', 'FEMALE'])) {
//             return 'F';
//         }

//         return 'Otro';
//     }

//     private function mapMoodState($value): string
//     {
//         if (empty($value)) return 'Regular';

//         $mood = strtolower(trim($value));

//         $moodMap = [
//             'muy bueno' => 'Muy Bueno',
//             'excelente' => 'Muy Bueno',
//             'bueno' => 'Bueno',
//             'bien' => 'Bueno',
//             'regular' => 'Regular',
//             'normal' => 'Regular',
//             'malo' => 'Malo',
//             'mal' => 'Malo',
//             'muy malo' => 'Muy Malo',
//             'terrible' => 'Muy Malo',
//         ];

//         return $moodMap[$mood] ?? 'Regular';
//     }

//     private function parseBoolean($value): bool
//     {
//         if (is_bool($value)) return $value;

//         $value = strtolower(trim($value));

//         return in_array($value, ['si', 'sí', 'yes', 'true', '1', 'verdadero']);
//     }

//     private function parseDate($value): ?Carbon
//     {
//         if (empty($value)) return null;

//         try {
//             // Intentar varios formatos de fecha
//             $formats = [
//                 'Y-m-d',
//                 'd/m/Y',
//                 'm/d/Y',
//                 'd-m-Y',
//                 'Y/m/d',
//                 'd/m/y',
//                 'm/d/y',
//             ];

//             foreach ($formats as $format) {
//                 try {
//                     return Carbon::createFromFormat($format, $value);
//                 } catch (\Exception $e) {
//                     continue;
//                 }
//             }

//             // Como último recurso, usar Carbon parse
//             return Carbon::parse($value);

//         } catch (\Exception $e) {
//             Log::warning("No se pudo parsear la fecha: {$value}");
//             return null;
//         }
//     }

//     public function rules(): array
//     {
//         return [
//             '*.numero_identificacion' => 'required|string|max:20',
//             '*.nombres' => 'required|string|max:100',
//             '*.apellidos' => 'required|string|max:100',
//             '*.edad' => 'nullable|integer|min:0|max:150',
//             '*.municipio' => 'nullable|string|max:100',
//         ];
//     }

//     public function getImportedCount(): int
//     {
//         return $this->importedCount;
//     }

//     public function getUpdatedCount(): int
//     {
//         return $this->updatedCount;
//     }

//     public function getSkippedCount(): int
//     {
//         return $this->skippedCount;
//     }

//     public function getErrors(): array
//     {
//         return $this->errors;
//     }
// }


namespace App\Imports;

use App\Models\Patient;
use App\Models\MonthlyFollowup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Carbon\Carbon;

class PatientsImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected $importedCount = 0;
    protected $updatedCount = 0;
    protected $skippedCount = 0;
    protected $errors = [];

    public function collection(Collection $collection)
    {
        DB::beginTransaction();

        try {
            foreach ($collection as $row) {
                $this->processRow($row);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error en importación masiva: ' . $e->getMessage());
            throw $e;
        }
    }

    private function processRow(Collection $row)
    {
        // Limpiar y validar datos
        $documentNumber = $this->cleanString($row['documento'] ?? $row['cedula'] ?? $row['numero_documento'] ?? $row['document_number']);

        if (empty($documentNumber)) {
            $this->skippedCount++;
            $this->errors[] = "Fila sin número de documento saltada";
            return;
        }

        // Buscar si el paciente ya existe por número de documento
        $patient = Patient::where('document_number', $documentNumber)->first();

        $patientData = $this->mapPatientData($row);

        if ($patient) {
            // Actualizar datos del paciente existente
            $patient->update($patientData);
            $this->updatedCount++;
        } else {
            // Crear nuevo paciente
            $patient = Patient::create($patientData);
            $this->importedCount++;
        }

        // Procesar seguimiento mensual si existe información
        $this->processFollowup($patient, $row);
    }

    private function mapPatientData(Collection $row): array
    {
        $birthDate = $this->parseDate($row['fecha_nacimiento'] ?? $row['birth_date'] ?? null);

        return [
            'document_number' => $this->cleanString($row['documento'] ?? $row['cedula'] ?? $row['numero_documento'] ?? $row['document_number']),
            'document_type' => $this->mapDocumentType($row['tipo_documento'] ?? $row['document_type'] ?? 'CC'),
            'full_name' => $this->cleanString($row['nombre_completo'] ?? $row['nombres'] ?? $row['full_name']),
            'gender' => $this->mapGender($row['genero'] ?? $row['sexo'] ?? $row['gender']),
            'birth_date' => $birthDate ? $birthDate->format('Y-m-d') : null,
            'phone' => $this->cleanString($row['telefono'] ?? $row['celular'] ?? $row['phone']),
            'address' => $this->cleanString($row['direccion'] ?? $row['address']),
            'neighborhood' => $this->cleanString($row['barrio'] ?? $row['neighborhood']),
            'village' => $this->cleanString($row['vereda'] ?? $row['village']),
            'eps_code' => $this->cleanString($row['codigo_eps'] ?? $row['eps_code']),
            'eps_name' => $this->cleanString($row['nombre_eps'] ?? $row['eps_name'] ?? $row['eps']),
            'status' => $this->mapStatus($row['estado'] ?? $row['status'] ?? 'active'),
        ];
    }

    private function processFollowup(Patient $patient, Collection $row)
    {
        // Verificar si hay información de seguimiento en la fila
        $followupDate = $this->parseDate($row['fecha_seguimiento'] ?? $row['followup_date'] ?? null);

        if (!$followupDate) {
            return; // No hay información de seguimiento
        }

        $year = $followupDate->year;
        $month = $followupDate->month;

        // Verificar si ya existe un seguimiento para esta fecha y paciente
        $existingFollowup = MonthlyFollowup::where('followupable_id', $patient->id)
            ->where('followupable_type', Patient::class)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($existingFollowup) {
            return; // Ya existe seguimiento para este mes
        }

        $actions = [];
        if ($actionText = $this->cleanString($row['acciones'] ?? $row['actions_taken'] ?? null)) {
            $actions = explode(',', $actionText);
            $actions = array_map('trim', $actions);
        }

        $followupData = [
            'followupable_id' => $patient->id,
            'followupable_type' => Patient::class,
            'followup_date' => $followupDate->format('Y-m-d'),
            'year' => $year,
            'month' => $month,
            'description' => $this->cleanString($row['descripcion'] ?? $row['observaciones'] ?? $row['description'] ?? 'Seguimiento importado desde Excel'),
            'status' => $this->mapFollowupStatus($row['estado_seguimiento'] ?? $row['followup_status'] ?? 'completed'),
            'next_followup' => $this->parseDate($row['proxima_cita'] ?? $row['next_followup'] ?? null)?->format('Y-m-d'),
            'actions_taken' => !empty($actions) ? json_encode($actions) : null,
            'performed_by' => auth()->id() ?? 1, // Usuario por defecto si no hay autenticación
        ];

        MonthlyFollowup::create($followupData);
    }

    // Métodos de limpieza y mapeo
    private function cleanString($value): ?string
    {
        if (empty($value)) return null;
        return trim(strip_tags((string) $value));
    }

    private function mapDocumentType($value): string
    {
        if (empty($value)) return 'CC';

        $docType = strtoupper(trim($value));

        $validTypes = ['CC', 'TI', 'CE', 'PA', 'RC', 'MS', 'AS', 'CN'];

        // Mapeo de valores comunes
        $mappings = [
            'CEDULA' => 'CC',
            'CEDULA DE CIUDADANIA' => 'CC',
            'TARJETA DE IDENTIDAD' => 'TI',
            'CEDULA EXTRANJERIA' => 'CE',
            'PASAPORTE' => 'PA',
            'REGISTRO CIVIL' => 'RC',
        ];

        if (isset($mappings[$docType])) {
            return $mappings[$docType];
        }

        return in_array($docType, $validTypes) ? $docType : 'CC';
    }

    private function mapGender($value): string
    {
        if (empty($value)) return 'Otro';

        $gender = strtoupper(trim($value));

        if (in_array($gender, ['M', 'MASCULINO', 'HOMBRE', 'MALE'])) {
            return 'Masculino';
        } elseif (in_array($gender, ['F', 'FEMENINO', 'MUJER', 'FEMALE'])) {
            return 'Femenino';
        }

        return 'Otro';
    }

    private function mapStatus($value): string
    {
        if (empty($value)) return 'active';

        $status = strtolower(trim($value));

        $mappings = [
            'activo' => 'active',
            'inactivo' => 'inactive',
            'alta' => 'discharged',
            'dado de alta' => 'discharged',
            'egresado' => 'discharged',
        ];

        return $mappings[$status] ?? 'active';
    }

    private function mapFollowupStatus($value): string
    {
        if (empty($value)) return 'completed';

        $status = strtolower(trim($value));

        $mappings = [
            'completado' => 'completed',
            'realizado' => 'completed',
            'pendiente' => 'pending',
            'no contactado' => 'not_contacted',
            'no localizado' => 'not_contacted',
            'rechazado' => 'refused',
            'rehusado' => 'refused',
        ];

        return $mappings[$status] ?? 'completed';
    }

    private function parseDate($value): ?Carbon
    {
        if (empty($value)) return null;

        try {
            // Intentar varios formatos de fecha
            $formats = [
                'Y-m-d',
                'd/m/Y',
                'm/d/Y',
                'd-m-Y',
                'Y/m/d',
                'd/m/y',
                'm/d/y',
                'd.m.Y',
                'Y-m-d H:i:s',
            ];

            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, trim($value));
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Como último recurso, usar Carbon parse
            return Carbon::parse($value);
        } catch (\Exception $e) {
            Log::warning("No se pudo parsear la fecha: {$value}");
            return null;
        }
    }

    public function rules(): array
    {
        return [
            '*.documento' => 'required|string|max:20',
            '*.nombre_completo' => 'required|string|max:255',
            '*.genero' => 'nullable|string|max:20',
            '*.fecha_nacimiento' => 'nullable|date',
        ];
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Obtener las columnas esperadas para la plantilla
     */
    public static function getExpectedColumns(): array
    {
        return [
            // Columnas obligatorias
            'obligatory' => [
                'documento' => 'Número de documento (CC, TI, etc.)',
                'nombre_completo' => 'Nombre completo del paciente',
                'genero' => 'Masculino, Femenino, Otro',
            ],

            // Columnas opcionales del paciente
            'optional' => [
                'tipo_documento' => 'CC, TI, CE, PA, RC, etc.',
                'fecha_nacimiento' => 'YYYY-MM-DD o DD/MM/YYYY',
                'telefono' => 'Número de teléfono o celular',
                'direccion' => 'Dirección residencial',
                'barrio' => 'Barrio de residencia',
                'vereda' => 'Vereda (zona rural)',
                'codigo_eps' => 'Código de la EPS',
                'nombre_eps' => 'Nombre de la EPS',
                'estado' => 'active, inactive, discharged',
            ],

            // Columnas de seguimiento opcionales
            'followup' => [
                'fecha_seguimiento' => 'Fecha del seguimiento realizado',
                'descripcion' => 'Descripción del seguimiento',
                'estado_seguimiento' => 'completed, pending, not_contacted, refused',
                'acciones' => 'Acciones realizadas (separadas por comas)',
                'proxima_cita' => 'Fecha de próxima cita',
            ]
        ];
    }
}
