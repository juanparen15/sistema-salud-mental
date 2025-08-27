<?php

// namespace App\Console\Commands;

// use App\Imports\MentalHealthSystemImport;
// use App\Imports\PatientsImport;
// use Illuminate\Console\Command;
// use Maatwebsite\Excel\Facades\Excel;
// use Illuminate\Support\Facades\Storage;
// use Illuminate\Support\Facades\File;

// class ImportMentalHealthData extends Command
// {
//     /**
//      * The name and signature of the console command.
//      */
//     protected $signature = 'mental-health:import 
//                             {file : Ruta del archivo Excel} 
//                             {--type=auto : Tipo de importación (auto, system, generic)} 
//                             {--disk=local : Disco donde está el archivo}';

//     /**
//      * The console command description.
//      */
//     protected $description = 'Importar datos del Sistema de Salud Mental desde archivo Excel';

//     /**
//      * Execute the console command.
//      */
//     public function handle()
//     {
//         $filePath = $this->argument('file');
//         $type = $this->option('type');
//         $disk = $this->option('disk');

//         // Verificar si el archivo existe
//         if (!Storage::disk($disk)->exists($filePath) && !File::exists($filePath)) {
//             $this->error("❌ El archivo no existe: {$filePath}");
//             return 1;
//         }

//         // Usar ruta absoluta si no está en storage
//         if (!Storage::disk($disk)->exists($filePath) && File::exists($filePath)) {
//             $fullPath = $filePath;
//         } else {
//             $fullPath = Storage::disk($disk)->path($filePath);
//         }

//         $this->info("🚀 Iniciando importación...");
//         $this->info("📁 Archivo: {$filePath}");
//         $this->info("⚙️  Tipo: {$type}");
//         $this->newLine();

//         try {
//             // Determinar tipo de importación automáticamente
//             if ($type === 'auto') {
//                 $type = $this->detectImportType($fullPath);
//                 $this->info("🔍 Tipo detectado automáticamente: {$type}");
//             }

//             $startTime = microtime(true);

//             if ($type === 'system') {
//                 $this->importMentalHealthSystem($fullPath);
//             } else {
//                 $this->importGeneric($fullPath);
//             }

//             $endTime = microtime(true);
//             $duration = round($endTime - $startTime, 2);

//             $this->newLine();
//             $this->info("⏱️  Tiempo total: {$duration} segundos");
//             $this->info("✅ Importación completada exitosamente!");

//         } catch (\Exception $e) {
//             $this->error("❌ Error durante la importación: " . $e->getMessage());
//             $this->error("📝 Detalles: " . $e->getFile() . ':' . $e->getLine());
            
//             if ($this->option('verbose')) {
//                 $this->error($e->getTraceAsString());
//             }
            
//             return 1;
//         }

//         return 0;
//     }

//     private function detectImportType(string $filePath): string
//     {
//         try {
//             // Intentar detectar las hojas del archivo
//             $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
//             $spreadsheet = $reader->load($filePath);
//             $sheetNames = $spreadsheet->getSheetNames();

//             $this->info("📊 Hojas encontradas: " . implode(', ', $sheetNames));

//             // Verificar si tiene las hojas del sistema de salud mental
//             $systemSheets = ['TRASTORNOS 2025', 'EVENTO 356 2025', 'CONSUMO SPA 2025'];
//             $hasSystemSheets = array_intersect($systemSheets, $sheetNames);

//             if (count($hasSystemSheets) >= 2) {
//                 return 'system';
//             }

//             return 'generic';

//         } catch (\Exception $e) {
//             $this->warn("⚠️  No se pudo detectar el tipo automáticamente, usando genérico");
//             return 'generic';
//         }
//     }

//     private function importMentalHealthSystem(string $filePath): void
//     {
//         $this->info("🏥 Importando usando Sistema de Salud Mental...");
        
//         $progressBar = $this->output->createProgressBar(3);
//         $progressBar->setFormat('🔄 Procesando: %current%/%max% hojas [%bar%] %percent:3s%%');
//         $progressBar->start();

//         $import = new MentalHealthSystemImport();
//         Excel::import($import, $filePath);
//         $progressBar->advance(3);
//         $progressBar->finish();

//         $this->newLine(2);
//         $this->showMentalHealthStats($import);
//     }

//     private function importGeneric(string $filePath): void
//     {
//         $this->info("📄 Importando usando importador genérico...");
        
//         $import = new PatientsImport();
        
//         $this->withProgressBar([1], function () use ($import, $filePath) {
//             Excel::import($import, $filePath);
//         });

//         $this->newLine();
//         $this->showGenericStats($import);
//     }

//     private function showMentalHealthStats(MentalHealthSystemImport $import): void
//     {
//         $this->info("📊 Estadísticas de Importación:");
//         $this->info("  📋 Pacientes nuevos: " . $import->getImportedCount());
//         $this->info("  🔄 Pacientes actualizados: " . $import->getUpdatedCount());
//         $this->info("  📅 Seguimientos creados: " . $import->getFollowupsCreated());
        
//         if ($import->getSkippedCount() > 0) {
//             $this->warn("  ⏭️  Registros omitidos: " . $import->getSkippedCount());
//         }

//         if (!empty($import->getErrors())) {
//             $this->warn("⚠️  Advertencias encontradas:");
//             foreach (array_slice($import->getErrors(), 0, 10) as $error) {
//                 $this->warn("  • {$error}");
//             }
            
//             if (count($import->getErrors()) > 10) {
//                 $this->warn("  ... y " . (count($import->getErrors()) - 10) . " más");
//             }
//         }
//     }

//     private function showGenericStats(PatientsImport $import): void
//     {
//         $this->info("📊 Estadísticas de Importación:");
//         $this->info("  ✅ Pacientes nuevos: " . $import->getImportedCount());
//         $this->info("  🔄 Pacientes actualizados: " . $import->getUpdatedCount());
        
//         if ($import->getSkippedCount() > 0) {
//             $this->warn("  ⏭️  Registros omitidos: " . $import->getSkippedCount());
//         }

//         if (!empty($import->getErrors())) {
//             $this->warn("⚠️  Errores encontrados:");
//             foreach (array_slice($import->getErrors(), 0, 5) as $error) {
//                 $this->warn("  • {$error}");
//             }
//         }
//     }
// }

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

// // ==================== HOJA TRASTORNOS CORREGIDA ====================
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
//             // Extraer y limpiar datos básicos
//             $documentNumber = $this->cleanString($row['n_documento']);
            
//             // Saltar filas vacías o inválidas
//             if (empty($documentNumber) || !$this->isValidDocument($documentNumber)) {
//                 $this->parent->incrementSkipped();
//                 return; // No agregar error para filas vacías
//             }

//             $fullName = $this->cleanString($row['nombres_y_apellidos']);
//             if (empty($fullName) || strlen($fullName) < 3) {
//                 $this->parent->incrementSkipped();
//                 $this->parent->addError("TRASTORNOS Fila {$rowNumber}: Nombre inválido (doc: {$documentNumber})");
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

//     private function isValidDocument($document): bool
//     {
//         // Verificar que sea numérico y tenga longitud razonable
//         $clean = preg_replace('/\D/', '', (string)$document);
//         return !empty($clean) && strlen($clean) >= 6 && strlen($clean) <= 15;
//     }

//     private function createOrUpdatePatient(Collection $row, int $rowNumber, string $eventType)
//     {
//         $documentNumber = $this->cleanString($row['n_documento']);
//         $patient = Patient::where('document_number', $documentNumber)->first();

//         // Limpiar teléfono - tomar solo el primer número si hay múltiples
//         $phone = $this->cleanPhone($row['telefono']);
        
//         $patientData = [
//             'document_number' => $documentNumber,
//             'document_type' => $this->mapDocumentType($row['tipo_de_documento']),
//             'full_name' => $this->truncateString($this->cleanString($row['nombres_y_apellidos']), 255),
//             'gender' => $this->mapGender($row['sex0']),
//             'birth_date' => $this->parseDate($row['fecha_de_nacimiento'])?->format('Y-m-d'),
//             'phone' => $phone,
//             'address' => $this->truncateString($this->cleanString($row['direccion']), 255),
//             'village' => $this->truncateString($this->cleanString($row['vereda']), 255),
//             'eps_code' => $this->truncateString($this->cleanString($row['eps_codigo']), 255),
//             'eps_name' => $this->truncateString($this->cleanString($row['eps_nombre']), 255),
//             'status' => 'active',
//         ];

//         // Filtrar valores nulos y validar longitudes
//         $patientData = array_filter($patientData, fn($value) => $value !== null && $value !== '');

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
//             $errorMsg = "TRASTORNOS Fila {$rowNumber}: Error BD - " . $e->getMessage();
//             $this->parent->addError($errorMsg);
//             Log::error($errorMsg, ['data' => $patientData]);
//             return null;
//         }
//     }

//     private function cleanPhone($phone): ?string
//     {
//         if (empty($phone)) return null;
        
//         $phone = (string)$phone;
        
//         // Si hay múltiples números separados por guión, coma o espacio, tomar el primero
//         $phones = preg_split('/[-,\s\/]/', $phone);
//         $firstPhone = trim($phones[0]);
        
//         // Limpiar caracteres no numéricos
//         $cleaned = preg_replace('/\D/', '', $firstPhone);
        
//         // Limitar a 20 caracteres máximo
//         if (strlen($cleaned) > 20) {
//             $cleaned = substr($cleaned, 0, 20);
//         }
        
//         // Validar longitud mínima
//         return strlen($cleaned) >= 7 ? $cleaned : null;
//     }

//     private function truncateString($string, $maxLength): ?string
//     {
//         if (empty($string)) return null;
//         return strlen($string) > $maxLength ? substr($string, 0, $maxLength) : $string;
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
            
//             if (empty($followupData) || strlen($followupData) < 2) continue;

//             // Verificar si ya existe seguimiento para este mes
//             $existingFollowup = MonthlyFollowup::where('followupable_id', $patient->id)
//                 ->where('followupable_type', Patient::class)
//                 ->where('year', 2025)
//                 ->where('month', $monthNumber)
//                 ->first();

//             if ($existingFollowup) continue; // Ya existe

//             try {
//                 // Crear descripción limitada
//                 $description = "Seguimiento TRASTORNO - " . substr($followupData, 0, 500);
                
//                 // Agregar información específica de trastornos (limitada)
//                 $additionalInfo = [];
//                 if (!empty($row['diagnostico'])) {
//                     $additionalInfo[] = "Dx: " . substr($this->cleanString($row['diagnostico']), 0, 100);
//                 }
//                 if (!empty($row['observacion_adicional'])) {
//                     $additionalInfo[] = "Obs: " . substr($this->cleanString($row['observacion_adicional']), 0, 100);
//                 }
                
//                 if (!empty($additionalInfo)) {
//                     $description .= " | " . implode(" | ", $additionalInfo);
//                 }

//                 // Limitar descripción total
//                 $description = substr($description, 0, 1000);

//                 MonthlyFollowup::create([
//                     'followupable_id' => $patient->id,
//                     'followupable_type' => Patient::class,
//                     'followup_date' => Carbon::create(2025, $monthNumber, 15)->format('Y-m-d'),
//                     'year' => 2025,
//                     'month' => $monthNumber,
//                     'description' => $description,
//                     'status' => 'completed',
//                     'actions_taken' => json_encode(['Seguimiento de trastorno mental']),
//                     'performed_by' => auth()->id() ?? 1,
//                 ]);

//                 $this->parent->incrementFollowups();

//             } catch (\Exception $e) {
//                 $this->parent->addError("TRASTORNOS Fila {$rowNumber}: Error creando seguimiento mes {$monthNumber} - " . $e->getMessage());
//             }
//         }
//     }

//     private function cleanString($value): ?string
//     {
//         if (empty($value)) return null;
//         $cleaned = trim(strip_tags((string)$value));
//         return $cleaned === '' ? null : $cleaned;
//     }

//     private function mapDocumentType($value): string
//     {
//         if (empty($value)) return 'CC';
//         $type = strtoupper(trim((string)$value));
//         return in_array($type, ['CC', 'TI', 'CE', 'PA', 'RC', 'MS', 'AS', 'CN']) ? $type : 'CC';
//     }

//     private function mapGender($value): string
//     {
//         if (empty($value)) return 'Otro';
//         $gender = strtoupper(trim((string)$value));
//         if (in_array($gender, ['M', 'MASCULINO', 'HOMBRE'])) return 'Masculino';
//         if (in_array($gender, ['F', 'FEMENINO', 'MUJER'])) return 'Femenino';
//         return 'Otro';
//     }

//     private function parseDate($value): ?Carbon
//     {
//         if (empty($value)) return null;
//         try {
//             // Manejar números de Excel
//             if (is_numeric($value)) {
//                 return Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays($value);
//             }
//             return Carbon::parse($value);
//         } catch (\Exception $e) {
//             return null;
//         }
//     }
// }

// // ==================== HOJA EVENTO 356 CORREGIDA ====================
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
            
//             // Saltar filas vacías sin generar error
//             if (empty($documentNumber) || !$this->isValidDocument($documentNumber)) {
//                 $this->parent->incrementSkipped();
//                 return;
//             }

//             $fullName = $this->cleanString($row['nombres_y_apellidos']);
//             if (empty($fullName) || strlen($fullName) < 3) {
//                 $this->parent->incrementSkipped();
//                 return;
//             }

//             $patient = $this->createOrUpdatePatient($row, $rowNumber, $eventType);
//             if (!$patient) return;

//             $this->processMonthlyFollowups($patient, $row, $rowNumber, $eventType);

//         } catch (\Exception $e) {
//             $this->parent->addError("EVENTO 356 Fila {$rowNumber}: " . $e->getMessage());
//         }
//     }

//     private function isValidDocument($document): bool
//     {
//         $clean = preg_replace('/\D/', '', (string)$document);
//         return !empty($clean) && strlen($clean) >= 6 && strlen($clean) <= 15;
//     }

//     private function createOrUpdatePatient(Collection $row, int $rowNumber, string $eventType)
//     {
//         $documentNumber = $this->cleanString($row['n_documento']);
//         $patient = Patient::where('document_number', $documentNumber)->first();

//         $patientData = [
//             'document_number' => $documentNumber,
//             'document_type' => $this->mapDocumentType($row['tipo_doc']),
//             'full_name' => $this->truncateString($this->cleanString($row['nombres_y_apellidos']), 255),
//             'gender' => $this->mapGender($row['sexo']),
//             'birth_date' => $this->parseDate($row['fecha_de_nacimiento'])?->format('Y-m-d'),
//             'phone' => $this->cleanPhone($row['telefono']),
//             'address' => $this->truncateString($this->cleanString($row['direccion']), 255),
//             'neighborhood' => $this->truncateString($this->cleanString($row['barrio']), 255),
//             'village' => $this->truncateString($this->cleanString($row['vereda']), 255),
//             'status' => 'active',
//         ];

//         $patientData = array_filter($patientData, fn($value) => $value !== null && $value !== '');

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
//             $this->parent->addError("EVENTO 356 Fila {$rowNumber}: Error BD - " . $e->getMessage());
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
            
//             if (empty($followupData) || strlen($followupData) < 2) continue;

//             $existingFollowup = MonthlyFollowup::where('followupable_id', $patient->id)
//                 ->where('followupable_type', Patient::class)
//                 ->where('year', 2025)
//                 ->where('month', $monthNumber)
//                 ->first();

//             if ($existingFollowup) continue;

//             try {
//                 // Crear descripción específica para intento de suicidio
//                 $description = "Seguimiento INTENTO SUICIDIO - " . substr($followupData, 0, 500);
                
//                 $additionalInfo = [];
//                 if (!empty($row['n_intentos'])) {
//                     $additionalInfo[] = "N° Intentos: " . $this->cleanString($row['n_intentos']);
//                 }
//                 if (!empty($row['desencadenante'])) {
//                     $additionalInfo[] = "Desencadenante: " . substr($this->cleanString($row['desencadenante']), 0, 100);
//                 }
//                 if (!empty($row['mecanismo'])) {
//                     $additionalInfo[] = "Mecanismo: " . substr($this->cleanString($row['mecanismo']), 0, 100);
//                 }

//                 if (!empty($additionalInfo)) {
//                     $description .= " | " . implode(" | ", $additionalInfo);
//                 }

//                 $description = substr($description, 0, 1000);

//                 $actions = ['Seguimiento intento suicidio'];
//                 if (!empty($row['factores_de_riesgo'])) {
//                     $actions[] = 'Eval. riesgo: ' . substr($this->cleanString($row['factores_de_riesgo']), 0, 200);
//                 }

//                 MonthlyFollowup::create([
//                     'followupable_id' => $patient->id,
//                     'followupable_type' => Patient::class,
//                     'followup_date' => Carbon::create(2025, $monthNumber, 15)->format('Y-m-d'),
//                     'year' => 2025,
//                     'month' => $monthNumber,
//                     'description' => $description,
//                     'status' => 'completed',
//                     'actions_taken' => json_encode($actions),
//                     'performed_by' => auth()->id() ?? 1,
//                 ]);

//                 $this->parent->incrementFollowups();

//             } catch (\Exception $e) {
//                 $this->parent->addError("EVENTO 356 Fila {$rowNumber}: Error creando seguimiento - " . $e->getMessage());
//             }
//         }
//     }

//     // Métodos helper
//     private function cleanString($value): ?string
//     {
//         if (empty($value)) return null;
//         $cleaned = trim(strip_tags((string)$value));
//         return $cleaned === '' ? null : $cleaned;
//     }
    
//     private function cleanPhone($phone): ?string
//     {
//         if (empty($phone)) return null;
//         $phones = preg_split('/[-,\s\/]/', (string)$phone);
//         $firstPhone = trim($phones[0]);
//         $cleaned = preg_replace('/\D/', '', $firstPhone);
//         return strlen($cleaned) >= 7 && strlen($cleaned) <= 20 ? $cleaned : null;
//     }
    
//     private function truncateString($string, $maxLength): ?string
//     {
//         if (empty($string)) return null;
//         return strlen($string) > $maxLength ? substr($string, 0, $maxLength) : $string;
//     }
    
//     private function mapDocumentType($value): string
//     {
//         if (empty($value)) return 'CC';
//         $type = strtoupper(trim((string)$value));
//         return in_array($type, ['CC', 'TI', 'CE', 'PA', 'RC']) ? $type : 'CC';
//     }
    
//     private function mapGender($value): string
//     {
//         if (empty($value)) return 'Otro';
//         $gender = strtoupper(trim((string)$value));
//         if (in_array($gender, ['M', 'MASCULINO', 'HOMBRE'])) return 'Masculino';
//         if (in_array($gender, ['F', 'FEMENINO', 'MUJER'])) return 'Femenino';
//         return 'Otro';
//     }
    
//     private function parseDate($value): ?Carbon
//     {
//         if (empty($value)) return null;
//         try {
//             return is_numeric($value) 
//                 ? Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays($value) 
//                 : Carbon::parse($value);
//         } catch (\Exception $e) {
//             return null;
//         }
//     }
// }

// // ==================== HOJA CONSUMO SPA CORREGIDA ====================
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
            
//             if (empty($documentNumber) || !$this->isValidDocument($documentNumber)) {
//                 $this->parent->incrementSkipped();
//                 return;
//             }

//             $patient = $this->createOrUpdatePatient($row, $rowNumber, $eventType);
//             if (!$patient) return;

//             $this->processMonthlyFollowups($patient, $row, $rowNumber, $eventType);

//         } catch (\Exception $e) {
//             $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: " . $e->getMessage());
//         }
//     }

//     private function isValidDocument($document): bool
//     {
//         $clean = preg_replace('/\D/', '', (string)$document);
//         return !empty($clean) && strlen($clean) >= 6 && strlen($clean) <= 15;
//     }

//     private function createOrUpdatePatient(Collection $row, int $rowNumber, string $eventType)
//     {
//         $documentNumber = $this->cleanString($row['n_documento']);
//         $patient = Patient::where('document_number', $documentNumber)->first();

//         $patientData = [
//             'document_number' => $documentNumber,
//             'document_type' => $this->mapDocumentType($row['tipo_doc']),
//             'full_name' => $this->truncateString($this->cleanString($row['nombre_completo']), 255),
//             'gender' => $this->mapGender($row['sexo']),
//             'birth_date' => $this->parseDate($row['fecha_de_nacimiento'])?->format('Y-m-d'),
//             'phone' => $this->cleanPhone($row['telefono']),
//             'eps_name' => $this->truncateString($this->cleanString($row['eps'] ?? $row['nombre']), 255),
//             'status' => 'active',
//         ];

//         $patientData = array_filter($patientData, fn($value) => $value !== null && $value !== '');

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
//             $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: Error BD - " . $e->getMessage());
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
            
//             if (empty($followupData) || strlen($followupData) < 2) continue;

//             $existingFollowup = MonthlyFollowup::where('followupable_id', $patient->id)
//                 ->where('followupable_type', Patient::class)
//                 ->where('year', 2025)
//                 ->where('month', $monthNumber)
//                 ->first();

//             if ($existingFollowup) continue;

//             try {
//                 $description = "Seguimiento CONSUMO SPA - " . substr($followupData, 0, 500);
                
//                 $additionalInfo = [];
//                 if (!empty($row['diagnostico'])) {
//                     $additionalInfo[] = "Dx: " . substr($this->cleanString($row['diagnostico']), 0, 100);
//                 }
//                 if (!empty($row['observacion_adicional'])) {
//                     $additionalInfo[] = "Obs: " . substr($this->cleanString($row['observacion_adicional']), 0, 100);
//                 }

//                 if (!empty($additionalInfo)) {
//                     $description .= " | " . implode(" | ", $additionalInfo);
//                 }

//                 $description = substr($description, 0, 1000);

//                 MonthlyFollowup::create([
//                     'followupable_id' => $patient->id,
//                     'followupable_type' => Patient::class,
//                     'followup_date' => Carbon::create(2025, $monthNumber, 15)->format('Y-m-d'),
//                     'year' => 2025,
//                     'month' => $monthNumber,
//                     'description' => $description,
//                     'status' => 'completed',
//                     'actions_taken' => json_encode(['Seguimiento consumo SPA']),
//                     'performed_by' => auth()->id() ?? 1,
//                 ]);

//                 $this->parent->incrementFollowups();

//             } catch (\Exception $e) {
//                 $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: Error creando seguimiento - " . $e->getMessage());
//             }
//         }
//     }

//     // Métodos helper (iguales que las otras hojas)
//     private function cleanString($value): ?string
//     {
//         if (empty($value)) return null;
//         $cleaned = trim(strip_tags((string)$value));
//         return $cleaned === '' ? null : $cleaned;
//     }
    
//     private function cleanPhone($phone): ?string
//     {
//         if (empty($phone)) return null;
//         $phones = preg_split('/[-,\s\/]/', (string)$phone);
//         $firstPhone = trim($phones[0]);
//         $cleaned = preg_replace('/\D/', '', $firstPhone);
//         return strlen($cleaned) >= 7 && strlen($cleaned) <= 20 ? $cleaned : null;
//     }
    
//     private function truncateString($string, $maxLength): ?string
//     {
//         if (empty($string)) return null;
//         return strlen($string) > $maxLength ? substr($string, 0, $maxLength) : $string;
//     }
    
//     // private function isValidDocument($document): bool
//     // {
//     //     $clean = preg_replace('/\D/', '', (string)$document);
//     //     return !empty($clean) && strlen($clean) >= 6 && strlen($clean) <= 15;
//     // }
    
//     private function mapDocumentType($value): string
//     {
//         if (empty($value)) return 'CC';
//         $type = strtoupper(trim((string)$value));
//         return in_array($type, ['CC', 'TI', 'CE', 'PA', 'RC']) ? $type : 'CC';
//     }
    
//     private function mapGender($value): string
//     {
//         if (empty($value)) return 'Otro';
//         $gender = strtoupper(trim((string)$value));
//         if (in_array($gender, ['M', 'MASCULINO', 'HOMBRE'])) return 'Masculino';
//         if (in_array($gender, ['F', 'FEMENINO', 'MUJER'])) return 'Femenino';
//         return 'Otro';
//     }
    
//     private function parseDate($value): ?Carbon
//     {
//         if (empty($value)) return null;
//         try {
//             return is_numeric($value) 
//                 ? Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays($value) 
//                 : Carbon::parse($value);
//         } catch (\Exception $e) {
//             return null;
//         }
//     }
// }