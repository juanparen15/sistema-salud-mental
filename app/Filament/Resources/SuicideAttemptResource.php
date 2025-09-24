<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SuicideAttemptResource\Pages;
use App\Models\SuicideAttempt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SuicideAttemptResource extends Resource
{
    protected static ?string $model = SuicideAttempt::class;
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationLabel = 'Intentos de Suicidio';
    protected static ?string $modelLabel = 'Intento de Suicidio';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationGroup = 'Gestión de Casos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Paciente')
                    ->schema([
                        Forms\Components\Select::make('patient_id')
                            ->label('Paciente')
                            ->relationship('patient', 'full_name')
                            ->searchable(['document_number', 'full_name'])
                            ->required()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('document_type')
                                            ->label('Tipo Doc')
                                            ->options([
                                                'CC' => 'CC',
                                                'TI' => 'TI',
                                                'CE' => 'CE',
                                            ])
                                            ->required(),
                                        Forms\Components\TextInput::make('document_number')
                                            ->label('Número')
                                            ->required(),
                                        Forms\Components\TextInput::make('full_name')
                                            ->label('Nombre Completo')
                                            ->required()
                                            ->columnSpan(2),
                                        Forms\Components\Select::make('gender')
                                            ->label('Sexo')
                                            ->options([
                                                'Masculino' => 'Masculino',
                                                'Femenino' => 'Femenino',
                                            ])
                                            ->required(),
                                        Forms\Components\DatePicker::make('birth_date')
                                            ->label('F. Nacimiento')
                                            ->required(),
                                        Forms\Components\TextInput::make('phone')
                                            ->label('Teléfono')
                                            ->tel(),
                                        Forms\Components\TextInput::make('address')
                                            ->label('Dirección'),
                                    ]),
                            ]),
                    ]),

                Forms\Components\Section::make('Información del Evento')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DateTimePicker::make('event_date')
                                    ->label('Fecha del Evento')
                                    ->required()
                                    ->default(now()),

                                Forms\Components\TextInput::make('week_number')
                                    ->label('Número de Semana')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(53)
                                    ->default(now()->weekOfYear),

                                Forms\Components\Select::make('admission_via')
                                    ->label('Ingreso Por')
                                    ->options([
                                        'URGENCIAS' => 'Urgencias',
                                        'CONSULTA_EXTERNA' => 'Consulta Externa',
                                        'HOSPITALIZACION' => 'Hospitalización',
                                        'REFERENCIA' => 'Referencia',
                                        'COMUNIDAD' => 'Comunidad',
                                    ])
                                    ->required(),

                                Forms\Components\TextInput::make('attempt_number')
                                    ->label('Número de Intento')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required(),

                                Forms\Components\TextInput::make('benefit_plan')
                                    ->label('Plan de Beneficios')
                                    ->maxLength(255),

                                Forms\Components\Select::make('status')
                                    ->label('Estado')
                                    ->options([
                                        'active' => 'Activo',
                                        'inactive' => 'Inactivo',
                                        'resolved' => 'Resuelto',
                                    ])
                                    ->default('active')
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('Factores y Mecanismo')
                    ->schema([
                        Forms\Components\TextInput::make('trigger_factor')
                            ->label('Factor Desencadenante')
                            ->required()
                            ->maxLength(500),

                        Forms\Components\TagsInput::make('risk_factors')
                            ->label('Factores de Riesgo')
                            ->placeholder('Agregue factores de riesgo')
                            ->suggestions([
                                'Violencia intrafamiliar',
                                'Problemas económicos',
                                'Problemas familiares',
                                'Problemas de pareja',
                                'Consumo de sustancias',
                                'Trastorno mental previo',
                                'Antecedente de intento previo',
                                'Pérdida reciente',
                                'Problemas laborales',
                                'Problemas académicos',
                                'Aislamiento social',
                                'Enfermedad crónica',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('mechanism')
                            ->label('Mecanismo Utilizado')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000),

                        Forms\Components\Textarea::make('additional_observation')
                            ->label('Observaciones Adicionales')
                            ->rows(4)
                            ->maxLength(2000),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('patient.document_number')
                    ->label('Documento')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('patient.full_name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('event_date')
                    ->label('Fecha Evento')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('attempt_number')
                    ->label('N° Intento')
                    ->badge()
                    ->color(fn(int $state): string => match (true) {
                        $state === 1 => 'warning',
                        $state === 2 => 'danger',
                        $state >= 3 => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('trigger_factor')
                    ->label('Desencadenante')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->trigger_factor),

                Tables\Columns\TagsColumn::make('risk_factors')
                    ->label('Factores de Riesgo')
                    ->limit(2),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'danger',
                        'inactive' => 'warning',
                        'resolved' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('followups_count')
                    ->label('Seguimientos')
                    ->counts('followups')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        'resolved' => 'Resuelto',
                    ]),

                Tables\Filters\Filter::make('multiple_attempts')
                    ->label('Múltiples Intentos')
                    ->query(fn(Builder $query): Builder => $query->where('attempt_number', '>', 1)),

                Tables\Filters\Filter::make('recent')
                    ->label('Últimos 30 días')
                    ->query(fn(Builder $query): Builder => $query->where('event_date', '>=', now()->subDays(30))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn () => auth()->user()->can('view_patients')),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()->can('edit_patients')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('delete_patients')),
                Tables\Actions\Action::make('add_followup')
                    ->label('Añadir Seguimiento')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->url(fn($record) => route('filament.admin.resources.monthly-followups.create', [
                        'patient_id' => $record->patient_id,
                        'source_type' => 'suicide_attempt',
                        'source_id' => $record->id
                    ]))
                    ->visible(fn () => auth()->user()->can('create_followups')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('delete_patients')),
                    Tables\Actions\ExportBulkAction::make()
                        ->visible(fn () => auth()->user()->can('export_patients')),
                ]),
            ])
            // ->headerActions([
            //     Tables\Actions\ImportAction::make()
            //         ->visible(fn () => auth()->user()->can('import_patients')),
            //     Tables\Actions\ExportAction::make()
            //         ->visible(fn () => auth()->user()->can('export_patients')),
            // ])
            ->defaultSort('event_date', 'desc');
    }

    // ✅ Filtrar registros según permisos
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Si no puede ver todos los pacientes, aplicar filtros
        if (!auth()->user()->can('view_any_patients')) {
            // Solo puede ver casos relacionados con sus pacientes asignados
            $query->whereHas('patient', function ($q) {
                $q->where('assigned_to', auth()->id());
            });
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuicideAttempts::route('/'),
            'create' => Pages\CreateSuicideAttempt::route('/create'),
            'edit' => Pages\EditSuicideAttempt::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'active')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function canViewAny(): bool
    {
        if (!auth()->check()) return false;
        
        // Solo roles especializados pueden ver intentos de suicidio
        return auth()->user()->hasAnyRole(['super_admin', 'admin', 'coordinator', 'psychologist', 'social_worker']);
    }

    public static function canCreate(): bool
    {
        if (!auth()->check()) return false;
        
        // Assistant NO puede registrar casos de suicidio
        return auth()->user()->hasAnyRole(['super_admin', 'admin', 'coordinator', 'psychologist', 'social_worker']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }
}