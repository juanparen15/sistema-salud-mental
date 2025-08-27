<?php

namespace App\Filament\Pages;

use App\Services\ExportService;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Storage;

class ReportPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Reportes';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Reportes';
    protected static string $view = 'filament.pages.report-page';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Generar Reporte Mensual')
                    ->description('Seleccione el periodo para generar el reporte consolidado')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('year')
                                    ->label('Año')
                                    ->options(array_combine(
                                        range(2024, 2030),
                                        range(2024, 2030)
                                    ))
                                    ->default(now()->year)
                                    ->required(),
                                
                                Forms\Components\Select::make('month')
                                    ->label('Mes')
                                    ->options([
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
                            ]),
                        
                        Forms\Components\CheckboxList::make('include_sections')
                            ->label('Secciones a incluir')
                            ->options([
                                'mental_disorders' => 'Trastornos Mentales',
                                'suicide_attempts' => 'Intentos de Suicidio',
                                'substance_consumptions' => 'Consumo de SPA',
                                'followups' => 'Seguimientos Mensuales',
                                'statistics' => 'Estadísticas Generales',
                            ])
                            ->default(['mental_disorders', 'suicide_attempts', 'substance_consumptions', 'followups', 'statistics'])
                            ->columns(2),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generar Reporte Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->action('generateReport')
                ->color('success'),
                
            Action::make('generatePdf')
                ->label('Generar Reporte PDF')
                ->icon('heroicon-o-document')
                ->action('generatePdfReport')
                ->color('primary'),
        ];
    }

    public function generateReport(): void
    {
        $data = $this->form->getState();
        
        try {
            $exportService = new ExportService();
            $fileName = $exportService->exportMonthlyReport($data['year'], $data['month']);
            
            Notification::make()
                ->title('Reporte generado exitosamente')
                ->success()
                ->body("El archivo {$fileName} ha sido creado.")
                ->actions([
                    \Filament\Notifications\Actions\Action::make('download')
                        ->label('Descargar')
                        ->url(Storage::url($fileName))
                        ->openUrlInNewTab(),
                ])
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al generar reporte')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function generatePdfReport(): void
    {
        // Implementación similar para PDF
        Notification::make()
            ->title('Generando reporte PDF')
            ->info()
            ->body('Esta funcionalidad estará disponible próximamente.')
            ->send();
    }
}