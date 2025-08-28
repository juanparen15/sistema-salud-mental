<?php

// namespace App\Filament\Resources\PatientResource\Pages;

// use App\Filament\Resources\PatientResource;
// use App\Imports\PatientsImport;
// use Filament\Actions;
// use Filament\Resources\Pages\ListRecords;
// use Filament\Notifications\Notification;
// use Maatwebsite\Excel\Facades\Excel;
// use Illuminate\Support\Facades\Storage;
// use Filament\Forms\Components\FileUpload;

// class ListPatients extends ListRecords
// {
//     protected static string $resource = PatientResource::class;

//     protected function getHeaderActions(): array
//     {
//         return [
//             Actions\CreateAction::make(),
            
//             Actions\Action::make('importQuick')
//                 ->label('Importación Rápida')
//                 ->icon('heroicon-o-arrow-up-tray')
//                 ->color('success')
//                 ->form([
//                     FileUpload::make('file')
//                         ->label('Archivo Excel')
//                         ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
//                         ->maxSize(10240)
//                         ->required()
//                         ->helperText('Sube un archivo .xlsx o .xls con los datos de los pacientes'),
//                 ])
//                 ->action(function (array $data) {
//                     try {
//                         $import = new PatientsImport();
//                         Excel::import($import, $data['file'], 'public');
                        
//                         // Limpiar archivo temporal
//                         Storage::disk('public')->delete($data['file']);
                        
//                         Notification::make()
//                             ->title('¡Importación Exitosa!')
//                             ->body("
//                                 ✅ {$import->getImportedCount()} pacientes nuevos<br>
//                                 🔄 {$import->getUpdatedCount()} pacientes actualizados<br>
//                                 ⏭️ {$import->getSkippedCount()} registros omitidos
//                             ")
//                             ->success()
//                             ->duration(8000)
//                             ->send();
                            
//                     } catch (\Exception $e) {
//                         Notification::make()
//                             ->title('Error en Importación')
//                             ->body('Error: ' . $e->getMessage())
//                             ->danger()
//                             ->send();
//                     }
//                 }),
            
//             Actions\Action::make('downloadTemplate')
//                 ->label('Plantilla Excel')
//                 ->icon('heroicon-o-document-arrow-down')
//                 ->color('gray')
//                 ->action(function () {
//                     $headers = [
//                         ['documento', 'tipo_documento', 'nombre_completo', 'genero', 'fecha_nacimiento', 'telefono', 'direccion', 'barrio', 'vereda', 'codigo_eps', 'nombre_eps', 'estado', 'fecha_seguimiento', 'descripcion', 'estado_seguimiento', 'acciones', 'proxima_cita'],
//                         ['12345678', 'CC', 'Juan Pérez López', 'Masculino', '1985-03-15', '3001234567', 'Calle 123 #45-67', 'Centro', 'La Esperanza', 'EPS001', 'Nueva EPS', 'active', '2025-01-15', 'Seguimiento inicial completado', 'completed', 'Evaluación psicológica, Terapia individual', '2025-02-15'],
//                         ['87654321', 'TI', 'Ana María Rodríguez', 'Femenino', '2006-08-22', '3009876543', 'Carrera 45 #12-34', 'Laureles', 'San José', 'EPS003', 'Sura EPS', 'active', '2025-01-16', 'Paciente requiere seguimiento', 'pending', 'Contacto telefónico', '2025-01-30'],
//                     ];

//                     $filename = 'plantilla_importacion_pacientes.csv';
//                     $handle = fopen('php://temp', 'w+');
                    
//                     foreach ($headers as $row) {
//                         fputcsv($handle, $row);
//                     }
                    
//                     rewind($handle);
//                     $csvContent = stream_get_contents($handle);
//                     fclose($handle);

//                     return response($csvContent)
//                         ->header('Content-Type', 'text/csv')
//                         ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
//                 }),
//         ];
//     }
// }

namespace App\Filament\Resources\PatientResource\Pages;

use App\Filament\Resources\PatientResource;
use App\Imports\MentalHealthSystemImport;
use App\Imports\PatientsImport; // Fallback para otros archivos
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;

class ListPatients extends ListRecords
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            
            Actions\Action::make('importMentalHealth')
                ->label('Importar Excel Salud Mental')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    Section::make('Archivo del Sistema de Salud Mental')
                        ->description('Sube tu archivo "SISTEMA DE INFORMACIÓN SALUD MENTAL 2025.xlsx" con las 3 hojas')
                        ->schema([
                            Radio::make('import_type')
                                ->label('Tipo de Importación')
                                ->options([
                                    'mental_health_system' => 'Sistema Salud Mental (3 hojas: TRASTORNOS, EVENTO 356, CONSUMO SPA)',
                                    'generic' => 'Archivo Excel genérico'
                                ])
                                ->default('mental_health_system')
                                ->reactive()
                                ->columnSpanFull(),
                                
                            FileUpload::make('file')
                                ->label('Archivo Excel del Sistema')
                                ->acceptedFileTypes([
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    'application/vnd.ms-excel'
                                ])
                                ->maxSize(20480) // 20MB para archivos grandes
                                ->required()
                                ->helperText(function (callable $get) {
                                    return $get('import_type') === 'mental_health_system' 
                                        ? '📋 Sube el archivo con las hojas: TRASTORNOS 2025, EVENTO 356 2025, CONSUMO SPA 2025'
                                        : '📄 Archivo Excel genérico con datos de pacientes';
                                })
                                ->columnSpanFull(),
                        ]),
                        
                    Section::make('Hojas que se Procesarán')
                        ->description('El sistema procesará automáticamente las siguientes hojas:')
                        ->schema([
                            Placeholder::make('sheets_info')
                                ->label('')
                                ->content(function (callable $get) {
                                    if ($get('import_type') === 'mental_health_system') {
                                        return '
                                            <div class="space-y-3">
                                                <div class="p-3 bg-blue-50 rounded border-l-4 border-blue-400">
                                                    <h4 class="font-medium text-blue-800">🧠 TRASTORNOS 2025</h4>
                                                    <p class="text-sm text-blue-600 mt-1">Pacientes con trastornos mentales y seguimientos mensuales</p>
                                                </div>
                                                
                                                <div class="p-3 bg-red-50 rounded border-l-4 border-red-400">
                                                    <h4 class="font-medium text-red-800">⚠️ EVENTO 356 2025</h4>
                                                    <p class="text-sm text-red-600 mt-1">Intentos de suicidio con factores de riesgo y seguimientos</p>
                                                </div>
                                                
                                                <div class="p-3 bg-orange-50 rounded border-l-4 border-orange-400">
                                                    <h4 class="font-medium text-orange-800">🚭 CONSUMO SPA 2025</h4>
                                                    <p class="text-sm text-orange-600 mt-1">Consumo de sustancias psicoactivas y seguimientos</p>
                                                </div>
                                                
                                                <div class="p-2 bg-green-50 rounded text-xs">
                                                    ✅ <strong>Seguimientos automáticos:</strong> Se crearán seguimientos por cada mes con información (ENE-DIC 2025)
                                                </div>
                                            </div>
                                        ';
                                    } else {
                                        return '
                                            <div class="p-3 bg-gray-50 rounded">
                                                <p class="text-sm text-gray-600">Se procesará como archivo genérico detectando columnas automáticamente.</p>
                                            </div>
                                        ';
                                    }
                                })
                                ->columnSpanFull(),
                        ])
                        ->visible(fn (callable $get) => $get('import_type') === 'mental_health_system'),
                ])
                ->action(function (array $data) {
                    try {
                        Notification::make()
                            ->title('🔄 Procesando archivo...')
                            ->body('La importación ha comenzado. Esto puede tomar varios minutos.')
                            ->info()
                            ->persistent()
                            ->send();

                        DB::beginTransaction();

                        if ($data['import_type'] === 'mental_health_system') {
                            // Usar importador especializado para el sistema de salud mental
                            $import = new MentalHealthSystemImport();
                            Excel::import($import, $data['file'], 'public');
                            
                            $totalPatients = $import->getImportedCount() + $import->getUpdatedCount();
                            $followupsCount = $import->getFollowupsCreated();
                            
                            $successMessage = "<div class='space-y-2'>";
                            $successMessage .= "<div><strong>📊 Resumen de Importación:</strong></div>";
                            $successMessage .= "<div>✅ <strong>{$import->getImportedCount()}</strong> pacientes nuevos creados</div>";
                            $successMessage .= "<div>🔄 <strong>{$import->getUpdatedCount()}</strong> pacientes actualizados</div>";
                            $successMessage .= "<div>📅 <strong>{$followupsCount}</strong> seguimientos mensuales creados</div>";
                            
                            if ($import->getSkippedCount() > 0) {
                                $successMessage .= "<div>⏭️ <strong>{$import->getSkippedCount()}</strong> registros omitidos</div>";
                            }
                            
                            $successMessage .= "<div class='mt-2 text-xs bg-green-100 p-2 rounded'>💡 Se procesaron las 3 hojas automáticamente</div>";
                            $successMessage .= "</div>";

                        } else {
                            // Usar importador genérico
                            $import = new PatientsImport();
                            Excel::import($import, $data['file'], 'public');
                            
                            $successMessage = "✅ <strong>{$import->getImportedCount()}</strong> pacientes nuevos<br>";
                            $successMessage .= "🔄 <strong>{$import->getUpdatedCount()}</strong> pacientes actualizados<br>";
                            $successMessage .= "⏭️ <strong>{$import->getSkippedCount()}</strong> registros omitidos";
                        }

                        // Limpiar archivo temporal
                        if (Storage::disk('public')->exists($data['file'])) {
                            Storage::disk('public')->delete($data['file']);
                        }

                        DB::commit();

                        Notification::make()
                            ->title('🎉 ¡Importación Completada!')
                            ->body($successMessage)
                            ->success()
                            ->duration(15000)
                            ->send();

                        // Mostrar errores/advertencias si las hay
                        if (count($import->getErrors()) > 0) {
                            $errorCount = count($import->getErrors());
                            $errorMessage = "Se encontraron <strong>{$errorCount}</strong> advertencias:<br><br>";
                            
                            foreach (array_slice($import->getErrors(), 0, 8) as $error) {
                                $errorMessage .= "• {$error}<br>";
                            }
                            
                            if ($errorCount > 8) {
                                $errorMessage .= "<br>... y " . ($errorCount - 8) . " advertencias más";
                            }

                            Notification::make()
                                ->title('⚠️ Advertencias de Importación')
                                ->body($errorMessage)
                                ->warning()
                                ->duration(20000)
                                ->send();
                        }
                            
                    } catch (\Exception $e) {
                        DB::rollBack();
                        
                        \Log::error('Error en importación especializada: ' . $e->getMessage(), [
                            'file' => $data['file'] ?? 'unknown',
                            'trace' => $e->getTraceAsString()
                        ]);

                        Notification::make()
                            ->title('❌ Error en la Importación')
                            ->body('Error: ' . $e->getMessage() . '<br><br>Revisa que el archivo tenga las hojas correctas.')
                            ->danger()
                            ->duration(20000)
                            ->send();
                    }
                })
                ->modalWidth('xl'),
            
            // Actions\Action::make('downloadTemplate')
            //     ->label('Descargar Plantilla')
            //     ->icon('heroicon-o-document-arrow-down')
            //     ->color('gray')
            //     ->action(function () {
            //         try {
            //             // Crear una plantilla simplificada basada en el sistema real
            //             $headers = [
            //                 // Primera hoja - Trastornos (simplificada)
            //                 [
            //                     'HOJA: TRASTORNOS 2025',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     ''
            //                 ],
            //                 [
            //                     'FECHA_DE_INGRESO',
            //                     'TIPO_DE_INGRESO', 
            //                     'N_DOCUMENTO',
            //                     'TIPO_DE_DOCUMENTO',
            //                     'NOMBRES_Y_APELLIDOS',
            //                     'SEX0',
            //                     'FECHA_DE_NACIMIENTO',
            //                     'TELEFONO',
            //                     'DIRECCION',
            //                     'VEREDA',
            //                     'EPS_CODIGO',
            //                     'EPS_NOMBRE',
            //                     'DIAGNOSTICO',
            //                     'OBSERVACION_ADICIONAL',
            //                     'ENERO_2025',
            //                     'FEBRERO_2025',
            //                     'MARZO_2025',
            //                     'ABRIL_2025',
            //                     'MAYO_2025',
            //                     'JUNIO_2025',
            //                     'DICIEMBRE_2025'
            //                 ],
            //                 [
            //                     '2025-01-15',
            //                     'Primera vez',
            //                     '12345678',
            //                     'CC',
            //                     'Juan Pérez López',
            //                     'M',
            //                     '1985-03-15',
            //                     '3001234567',
            //                     'Calle 123 #45-67',
            //                     'El Carmen',
            //                     'EPS001',
            //                     'Nueva EPS',
            //                     'F32 Episodio depresivo',
            //                     'Paciente colaborativo',
            //                     'Seguimiento inicial',
            //                     'Evolución favorable',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     'Evaluación final'
            //                 ],
            //                 [],
            //                 [
            //                     'HOJA: EVENTO 356 2025 (INTENTOS SUICIDIO)',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     '',
            //                     ''
            //                 ],
            //                 [
            //                     'FECHA_DE_INGRESO',
            //                     'N_DOCUMENTO',
            //                     'NOMBRES_Y_APELLIDOS',
            //                     'SEXO',
            //                     'TELEFONO',
            //                     'DIRECCION',
            //                     'BARRIO',
            //                     'N_INTENTOS',
            //                     'DESENCADENANTE',
            //                     'FACTORES_DE_RIESGO',
            //                     'MECANISMO',
            //                     'ENERO_2025',
            //                     'FEBRERO_2025',
            //                     'MARZO_2025',
            //                     'ABRIL_2025',
            //                     'MAYO_2025',
            //                     'DICIEMBRE_2025'
            //                 ],
            //                 [
            //                     '2025-01-20',
            //                     '87654321',
            //                     'Ana García',
            //                     'F',
            //                     '3009876543',
            //                     'Carrera 15',
            //                     'Centro',
            //                     '1',
            //                     'Problemas familiares',
            //                     'Depresión, aislamiento',
            //                     'Intoxicación medicamentos',
            //                     'Seguimiento crítico',
            //                     'Terapia intensiva',
            //                     '',
            //                     '',
            //                     '',
            //                     'Estabilizada'
            //                 ]
            //             ];

            //             $filename = 'plantilla_salud_mental_' . date('Y-m-d') . '.csv';
            //             $handle = fopen('php://temp', 'w+');
                        
            //             fwrite($handle, "\xEF\xBB\xBF");
                        
            //             foreach ($headers as $row) {
            //                 fputcsv($handle, $row, ',', '"');
            //             }
                        
            //             rewind($handle);
            //             $csvContent = stream_get_contents($handle);
            //             fclose($handle);

            //             return response($csvContent)
            //                 ->header('Content-Type', 'text/csv; charset=UTF-8')
            //                 ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

            //         } catch (\Exception $e) {
            //             Notification::make()
            //                 ->title('Error')
            //                 ->body('No se pudo generar la plantilla: ' . $e->getMessage())
            //                 ->danger()
            //                 ->send();
            //         }
            //     }),

            Actions\Action::make('importAdvanced')
                ->label('Importación Avanzada')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('primary')
                ->url('/admin/import-patients-page')
                ->openUrlInNewTab(false),

            Actions\Action::make('viewStats')
                ->label('Estadísticas')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->action(function () {
                    $stats = [
                        'total_patients' => \App\Models\Patient::count(),
                        'followups_2025' => \App\Models\MonthlyFollowup::where('year', 2025)->count(),
                        'recent_followups' => \App\Models\MonthlyFollowup::where('followup_date', '>=', now()->subDays(30))->count(),
                    ];

                    Notification::make()
                        ->title('📊 Estadísticas del Sistema')
                        ->body("
                            📋 <strong>{$stats['total_patients']}</strong> pacientes registrados<br>
                            📅 <strong>{$stats['followups_2025']}</strong> seguimientos en 2025<br>
                            🔄 <strong>{$stats['recent_followups']}</strong> seguimientos último mes
                        ")
                        ->info()
                        ->duration(10000)
                        ->send();
                })
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Aquí puedes agregar widgets de estadísticas si los tienes
        ];
    }
}