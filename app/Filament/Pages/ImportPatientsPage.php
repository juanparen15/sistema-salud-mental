<?php

namespace App\Filament\Pages;

use App\Imports\PatientsImport;
use Filament\Pages\Page;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ImportPatientsPage extends Page implements HasForms
{
    // use InteractsWithForms;

    // protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    // protected static string $view = 'filament.pages.import-patients-page';

    // protected static ?string $navigationLabel = 'Importar Pacientes';

    // protected static ?string $title = 'Importación Masiva de Pacientes';

    // protected static ?string $navigationGroup = 'Gestión de Pacientes';

    // protected static ?int $navigationSort = 10;

    // public ?array $data = [];

    // public function mount(): void
    // {
    //     $this->form->fill();
    // }

    // public function form(Form $form): Form
    // {
    //     return $form
    //         ->schema([
    //             Section::make('Importar Archivo Excel')
    //                 ->description('Sube un archivo Excel (.xlsx, .xls) con los datos de los pacientes. El sistema evitará duplicados basándose en el número de identificación.')
    //                 ->schema([
    //                     FileUpload::make('file')
    //                         ->label('Archivo Excel')
    //                         ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
    //                         ->maxSize(10240) // 10MB
    //                         ->required()
    //                         ->helperText('Formatos permitidos: .xlsx, .xls (máximo 10MB)')
    //                         ->columnSpanFull(),
    //                 ])
    //                 ->columns(1),

    //             Section::make('Formato del Archivo')
    //                 ->description('El archivo Excel debe contener las siguientes columnas (los nombres pueden variar):')
    //                 ->schema([
    //                     Placeholder::make('required_columns')
    //                         ->label('')
    //                         ->content('
    //                             <div class="grid grid-cols-2 gap-4 text-sm">
    //                                 <div>
    //                                     <strong>Columnas Obligatorias:</strong>
    //                                     <ul class="mt-1 list-disc list-inside text-gray-600">
    //                                         <li>numero_identificacion (o identificacion)</li>
    //                                         <li>nombres (o primer_nombre)</li>
    //                                         <li>apellidos (o primer_apellido)</li>
    //                                         <li>edad</li>
    //                                         <li>genero (o sexo)</li>
    //                                         <li>municipio</li>
    //                                     </ul>
    //                                 </div>
    //                                 <div>
    //                                     <strong>Columnas Opcionales:</strong>
    //                                     <ul class="mt-1 list-disc list-inside text-gray-600">
    //                                         <li>telefono (o celular)</li>
    //                                         <li>email (o correo)</li>
    //                                         <li>direccion</li>
    //                                         <li>contacto_emergencia</li>
    //                                         <li>telefono_emergencia</li>
    //                                     </ul>
    //                                 </div>
    //                             </div>
    //                             <div class="mt-4">
    //                                 <strong>Columnas para Seguimiento (Opcionales):</strong>
    //                                 <div class="grid grid-cols-2 gap-4 mt-1 text-gray-600">
    //                                     <ul class="list-disc list-inside">
    //                                         <li>fecha_seguimiento</li>
    //                                         <li>estado_animo</li>
    //                                         <li>riesgo_suicidio</li>
    //                                         <li>intento_suicidio</li>
    //                                         <li>consumo_sustancias</li>
    //                                     </ul>
    //                                     <ul class="list-disc list-inside">
    //                                         <li>tipo_sustancia</li>
    //                                         <li>intervencion_realizada</li>
    //                                         <li>remision_realizada</li>
    //                                         <li>observaciones</li>
    //                                     </ul>
    //                                 </div>
    //                             </div>
    //                         ')
    //                         ->columnSpanFull(),
    //                 ]),

    //             Section::make('Importante')
    //                 ->description('Información importante sobre la importación:')
    //                 ->schema([
    //                     Placeholder::make('import_info')
    //                         ->label('')
    //                         ->content('
    //                             <div class="space-y-2 text-sm text-gray-600">
    //                                 <p><strong class="text-green-600">✅ Sin duplicados:</strong> Si un paciente ya existe (mismo número de identificación), se actualizarán sus datos.</p>
    //                                 <p><strong class="text-blue-600">📅 Seguimientos:</strong> Si se incluye información de seguimiento, se creará automáticamente (sin duplicar fechas existentes).</p>
    //                                 <p><strong class="text-yellow-600">⚠️ Validación:</strong> Los datos se validarán automáticamente antes de guardar.</p>
    //                                 <p><strong class="text-purple-600">🔄 Actualización continua:</strong> Puedes importar el mismo archivo múltiples veces conforme se actualice.</p>
    //                             </div>
    //                         ')
    //                         ->columnSpanFull(),
    //                 ]),
    //         ])
    //         ->statePath('data');
    // }

    // protected function getFormActions(): array
    // {
    //     return [
    //         Action::make('import')
    //             ->label('Importar Archivo')
    //             ->icon('heroicon-o-arrow-up-tray')
    //             ->color('primary')
    //             ->action('importData')
    //             ->disabled(fn() => empty($this->data['file'])),

    //         Action::make('downloadTemplate')
    //             ->label('Descargar Plantilla')
    //             ->icon('heroicon-o-document-arrow-down')
    //             ->color('gray')
    //             ->action('downloadTemplate'),
    //     ];
    // }

    // public function importData()
    // {
    //     if (empty($this->data['file'])) {
    //         Notification::make()
    //             ->title('Error')
    //             ->body('Por favor selecciona un archivo para importar.')
    //             ->danger()
    //             ->send();
    //         return;
    //     }

    //     try {
    //         $filePath = $this->data['file'];
            
    //         Notification::make()
    //             ->title('Procesando...')
    //             ->body('La importación ha comenzado. Por favor espera.')
    //             ->info()
    //             ->send();

    //         $import = new PatientsImport();
    //         Excel::import($import, $filePath, 'public');

    //         // Limpiar el archivo temporal
    //         Storage::disk('public')->delete($filePath);
    //         $this->data['file'] = null;

    //         Notification::make()
    //             ->title('¡Importación Exitosa!')
    //             ->body("
    //                 ✅ {$import->getImportedCount()} pacientes nuevos creados<br>
    //                 🔄 {$import->getUpdatedCount()} pacientes actualizados<br>
    //                 ⏭️ {$import->getSkippedCount()} registros omitidos
    //             ")
    //             ->success()
    //             ->duration(10000)
    //             ->send();

    //         if (count($import->getErrors()) > 0) {
    //             Notification::make()
    //                 ->title('Advertencias')
    //                 ->body('Se encontraron algunos problemas menores. Revisa los registros para más detalles.')
    //                 ->warning()
    //                 ->send();
    //         }

    //     } catch (\Exception $e) {
    //         Notification::make()
    //             ->title('Error en la Importación')
    //             ->body('Ocurrió un error: ' . $e->getMessage())
    //             ->danger()
    //             ->duration(15000)
    //             ->send();
    //     }
    // }

    // public function downloadTemplate()
    // {
    //     try {
    //         // Crear un archivo de ejemplo con las columnas correctas
    //         $headers = [
    //             ['numero_identificacion', 'nombres', 'apellidos', 'edad', 'genero', 'telefono', 'email', 'direccion', 'municipio', 'contacto_emergencia', 'telefono_emergencia', 'fecha_seguimiento', 'estado_animo', 'riesgo_suicidio', 'intento_suicidio', 'consumo_sustancias', 'tipo_sustancia', 'intervencion_realizada', 'observaciones'],
    //             ['12345678', 'Juan Carlos', 'Pérez González', '35', 'M', '3001234567', 'juan@email.com', 'Calle 123 #45-67', 'Bogotá', 'María Pérez', '3007654321', '2025-01-15', 'Bueno', 'no', 'no', 'no', '', 'si', 'Paciente estable'],
    //             ['87654321', 'Ana María', 'López Rodríguez', '28', 'F', '3009876543', 'ana@email.com', 'Carrera 45 #12-34', 'Medellín', 'Carlos López', '3001112233', '2025-01-16', 'Regular', 'si', 'no', 'si', 'Alcohol', 'si', 'Seguimiento semanal requerido'],
    //         ];

    //         $filename = 'plantilla_importacion_pacientes.csv';
    //         $handle = fopen('php://temp', 'w+');
            
    //         foreach ($headers as $row) {
    //             fputcsv($handle, $row);
    //         }
            
    //         rewind($handle);
    //         $csvContent = stream_get_contents($handle);
    //         fclose($handle);

    //         return response($csvContent)
    //             ->header('Content-Type', 'text/csv')
    //             ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

    //     } catch (\Exception $e) {
    //         Notification::make()
    //             ->title('Error')
    //             ->body('No se pudo generar la plantilla: ' . $e->getMessage())
    //             ->danger()
    //             ->send();
    //     }
    // }
}