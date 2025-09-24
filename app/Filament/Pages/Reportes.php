<?php

namespace App\Filament\Pages;

use App\Services\ExportService;
use App\Models\MonthlyFollowup;
use App\Models\Patient;
use App\Models\MentalDisorder;
use App\Models\SuicideAttempt;
use App\Models\SubstanceConsumption;
use Exception;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Storage;

class Reportes extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Reportes y Estadísticas';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Reportes y Estadísticas';
    protected static ?string $modelLabel = 'Reportes y Estadísticas';
    protected static ?string $pluralModelLabel = 'Reportes y Estadísticas';

    protected static string $view = 'filament.pages.report-page';

    public ?array $data = [];

    public function mount(): void
    {
        // ✅ Verificar permisos antes de montar
        abort_unless(auth()->user()->can('view_reports'), 403);
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Configuración del Reporte')
                    ->description('Configure los parámetros para generar el reporte del Sistema de Salud Mental')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('year')
                                    ->label('Año')
                                    ->options(array_combine(
                                        range(2023, 2030),
                                        range(2023, 2030)
                                    ))
                                    ->default(now()->year)
                                    ->required(),

                                Forms\Components\Select::make('month')
                                    ->label('Mes')
                                    ->options([
                                        0 => 'Todo el año',
                                        1 => 'Enero',
                                        2 => 'Febrero',
                                        3 => 'Marzo',
                                        4 => 'Abril',
                                        5 => 'Mayo',
                                        6 => 'Junio',
                                        7 => 'Julio',
                                        8 => 'Agosto',
                                        9 => 'Septiembre',
                                        10 => 'Octubre',
                                        11 => 'Noviembre',
                                        12 => 'Diciembre',
                                    ])
                                    ->default(now()->month)
                                    ->required(),

                                Forms\Components\Select::make('format')
                                    ->label('Formato')
                                    ->options([
                                        'excel' => 'Excel (.xlsx)',
                                        'csv' => 'CSV (.csv)',
                                    ])
                                    ->default('excel')
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('Tipo de Reporte')
                    ->description('Seleccione el tipo específico de reporte a generar')
                    ->schema([
                        Forms\Components\Radio::make('report_type')
                            ->label('Tipo de Reporte')
                            ->options([
                                'consolidated' => 'Reporte Consolidado (Todos los tipos de casos)',
                                'mental_disorders' => 'Solo Trastornos Mentales',
                                'suicide_attempts' => 'Solo Intentos de Suicidio',
                                'substance_consumption' => 'Solo Consumo de SPA',
                                'followups_summary' => 'Resumen de Seguimientos',
                                'statistics' => 'Estadísticas Detalladas',
                            ])
                            ->default('consolidated')
                            ->required()
                            ->reactive(),
                    ]),

                Forms\Components\Section::make('Opciones Avanzadas')
                    ->description('Configuraciones adicionales del reporte')
                    ->schema([
                        Forms\Components\CheckboxList::make('include_details')
                            ->label('Información Adicional a Incluir')
                            ->options([
                                'patient_demographics' => 'Datos demográficos de pacientes',
                                'followup_details' => 'Detalles completos de seguimientos',
                                'diagnosis_codes' => 'Códigos de diagnóstico (CIE-10)',
                                'risk_factors' => 'Factores de riesgo (intentos suicidio)',
                                'substance_details' => 'Detalles de sustancias (SPA)',
                                'monthly_trends' => 'Tendencias mensuales',
                            ])
                            ->default(['patient_demographics', 'followup_details'])
                            ->columns(2),

                        Forms\Components\Toggle::make('include_inactive')
                            ->label('Incluir registros inactivos')
                            ->default(false)
                            ->helperText('Incluir pacientes y casos marcados como inactivos'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generar Reporte')
                ->icon('heroicon-o-document-arrow-down')
                ->action('generateReport')
                ->color('success')
                ->visible(fn() => auth()->user()->can('generate_reports')), // ✅ Control de permisos

            Action::make('preview')
                ->label('Vista Previa')
                ->icon('heroicon-o-eye')
                ->action('previewReport')
                ->color('info')
                ->visible(fn() => auth()->user()->can('view_reports')), // ✅ Control de permisos

            Action::make('quick_stats')
                ->label('Estadísticas Rápidas')
                ->icon('heroicon-o-chart-bar')
                ->action('showQuickStats')
                ->color('primary')
                ->visible(fn() => auth()->user()->can('view_analytics')), // ✅ Control de permisos
        ];
    }

    public function generateReport(): void
    {
        // ✅ Verificar permisos antes de generar
        abort_unless(auth()->user()->can('generate_reports'), 403);

        $data = $this->form->getState();

        try {
            Notification::make()
                ->title('Generando reporte...')
                ->info()
                ->body('El proceso puede tomar algunos minutos.')
                ->send();

            $exportService = new ExportService();
            $fileName = $exportService->generateReport(
                $data['year'],
                $data['month'],
                $data['report_type'],
                $data['format'] ?? 'excel',
                $data['include_details'] ?? [],
                $data['include_inactive'] ?? false
            );

            Notification::make()
                ->title('Reporte generado exitosamente')
                ->success()
                ->body("Archivo generado: {$fileName}")
                ->actions([
                    \Filament\Notifications\Actions\Action::make('download')
                        ->label('Descargar')
                        ->url(Storage::url($fileName))
                        ->openUrlInNewTab()
                        ->visible(fn() => auth()->user()->can('export_reports')), // ✅ Control de permisos
                ])
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->title('Error al generar reporte')
                ->danger()
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }

    public function previewReport(): void
    {
        // ✅ Verificar permisos
        abort_unless(auth()->user()->can('view_reports'), 403);

        $data = $this->form->getState();

        try {
            $exportService = new ExportService();
            $preview = $exportService->getReportPreview(
                $data['year'],
                $data['month'],
                $data['report_type']
            );

            Notification::make()
                ->title('Vista Previa del Reporte')
                ->info()
                ->body($preview)
                ->duration(10000)
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->title('Error en vista previa')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function showQuickStats(): void
    {
        // ✅ Verificar permisos
        abort_unless(auth()->user()->can('view_analytics'), 403);

        try {
            $stats = $this->getQuickStatistics();

            $message = "<div class='space-y-2 text-sm'>";
            $message .= "<div class='grid grid-cols-2 gap-2'>";
            $message .= "<div><strong>Total Pacientes:</strong> {$stats['total_patients']}</div>";
            $message .= "<div><strong>Trastornos Mentales:</strong> {$stats['mental_disorders']}</div>";
            $message .= "<div><strong>Intentos Suicidio:</strong> {$stats['suicide_attempts']}</div>";
            $message .= "<div><strong>Casos SPA:</strong> {$stats['substance_consumption']}</div>";
            $message .= "<div><strong>Seguimientos 2025:</strong> {$stats['followups_2025']}</div>";
            $message .= "<div><strong>Seguimientos Pendientes:</strong> {$stats['pending_followups']}</div>";
            $message .= "</div>";
            $message .= "</div>";

            Notification::make()
                ->title('Estadísticas Rápidas del Sistema')
                ->info()
                ->body($message)
                ->duration(12000)
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->title('Error al obtener estadísticas')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    private function getQuickStatistics(): array
    {
        return [
            'total_patients' => Patient::count(),
            'mental_disorders' => MentalDisorder::count(),
            'suicide_attempts' => SuicideAttempt::count(),
            'substance_consumption' => SubstanceConsumption::count(),
            'followups_2025' => MonthlyFollowup::where('year', 2025)->count(),
            'pending_followups' => MonthlyFollowup::where('status', 'pending')->count(),
            'recent_followups' => MonthlyFollowup::where('followup_date', '>=', now()->subDays(30))->count(),
        ];
    }

    public static function canAccess(): bool
    {
        if (!auth()->check()) return false;

        // Assistant NO puede ver reportes
        return auth()->user()->hasAnyRole(['super_admin', 'admin', 'coordinator', 'psychologist', 'social_worker']) &&
            auth()->user()->can('view_reports');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canAccess();
    }
}
