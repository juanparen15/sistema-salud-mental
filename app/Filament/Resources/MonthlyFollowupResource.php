<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MonthlyFollowupResource\Pages;
use App\Models\MonthlyFollowup;
use App\Models\Patient;
use App\Models\MentalDisorder;
use App\Models\SuicideAttempt;
use App\Models\SubstanceConsumption;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Support\Str;

class MonthlyFollowupResource extends Resource
{
    protected static ?string $model = MonthlyFollowup::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Seguimientos Mensuales';

    protected static ?string $modelLabel = 'Seguimiento Mensual';

    protected static ?string $pluralModelLabel = 'Seguimientos Mensuales';

    protected static ?string $navigationGroup = 'Gestión de Pacientes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Seguimiento')
                    ->schema([
                        // Selector de tipo de caso
                        // Forms\Components\Select::make('followupable_type')
                        //     ->label('Tipo de Caso')
                        //     ->options([
                        //         MentalDisorder::class => 'Trastorno Mental',
                        //         SuicideAttempt::class => 'Intento de Suicidio',
                        //         SubstanceConsumption::class => 'Consumo SPA',
                        //     ])
                        //     ->required()
                        //     ->reactive()
                        //     ->afterStateUpdated(function ($state, $set) {
                        //         $set('followupable_id', null); // Limpiar selección anterior
                        //     }),

                        Forms\Components\Select::make('followupable_type')
                            ->label('Tipo de Caso')
                            ->options([
                                MentalDisorder::class => 'Trastorno Mental',
                                SuicideAttempt::class => 'Intento de Suicidio',
                                SubstanceConsumption::class => 'Consumo SPA',
                            ])
                            ->required()
                            ->reactive()
                            ->disabled(fn() => (bool)request()->query('source_type'))
                            ->default(function () {
                                $sourceType = request()->query('source_type');
                                return match ($sourceType) {
                                    'mental_disorder' => MentalDisorder::class,
                                    'suicide_attempt' => SuicideAttempt::class,
                                    'substance_consumption' => SubstanceConsumption::class,
                                    default => null
                                };
                            })
                            ->afterStateUpdated(fn($state, $set) => $set('followupable_id', null)),

                        // Selector de caso específico basado en el tipo
                        // Forms\Components\Select::make('followupable_id')
                        //     ->label('Caso Específico')
                        //     ->options(function (callable $get) {
                        //         $type = $get('followupable_type');
                        //         if (!$type) return [];

                        //         return match ($type) {
                        //             MentalDisorder::class => MentalDisorder::with('patient')
                        //                 ->get()
                        //                 ->mapWithKeys(function ($case) {
                        //                     return [
                        //                         $case->id =>
                        //                         $case->patient->full_name . ' - ' .
                        //                             $case->patient->document_number . ' | ' .
                        //                             ($case->diagnosis_code ?? 'Sin código') . ' - ' .
                        //                             Str::limit($case->diagnosis_description ?? 'Sin diagnóstico', 40)
                        //                     ];
                        //                 }),
                        //             SuicideAttempt::class => SuicideAttempt::with('patient')
                        //                 ->get()
                        //                 ->mapWithKeys(function ($case) {
                        //                     return [
                        //                         $case->id =>
                        //                         $case->patient->full_name . ' - ' .
                        //                             $case->patient->document_number . ' | ' .
                        //                             'Intento #' . ($case->attempt_number ?? '1') . ' - ' .
                        //                             Str::limit($case->mechanism ?? 'Sin mecanismo', 40)
                        //                     ];
                        //                 }),
                        //             SubstanceConsumption::class => SubstanceConsumption::with('patient')
                        //                 ->get()
                        //                 ->mapWithKeys(function ($case) {
                        //                     return [
                        //                         $case->id =>
                        //                         $case->patient->full_name . ' - ' .
                        //                             $case->patient->document_number . ' | ' .
                        //                             ($case->consumption_level ?? 'Sin nivel') . ' - ' .
                        //                             Str::limit($case->diagnosis ?? 'Sin diagnóstico', 40)
                        //                     ];
                        //                 }),
                        //             default => []
                        //         };
                        //     })
                        //     ->searchable()
                        //     ->required()
                        //     ->columnSpanFull()
                        //     ->placeholder('Primero selecciona el tipo de caso'),

                        Forms\Components\Select::make('followupable_id')
                            ->label('Caso Específico')
                            ->options(function (callable $get) {
                                $type = $get('followupable_type');
                                if (!$type) return [];
                                return match ($type) {
                                    MentalDisorder::class => MentalDisorder::with('patient')->get()->mapWithKeys(fn($case) => [
                                        $case->id => $case->patient->full_name . ' - ' . $case->patient->document_number
                                    ]),
                                    SuicideAttempt::class => SuicideAttempt::with('patient')->get()->mapWithKeys(fn($case) => [
                                        $case->id => $case->patient->full_name . ' - ' . $case->patient->document_number
                                    ]),
                                    SubstanceConsumption::class => SubstanceConsumption::with('patient')->get()->mapWithKeys(fn($case) => [
                                        $case->id => $case->patient->full_name . ' - ' . $case->patient->document_number
                                    ]),
                                    default => []
                                };
                            })
                            ->searchable()
                            ->required()
                            ->columnSpanFull()
                            ->disabled(fn() => (bool)request()->query('source_id'))
                            ->default(function () {
                                return request()->query('source_id');
                            })
                            ->placeholder('Primero selecciona el tipo de caso'),

                        Forms\Components\DatePicker::make('followup_date')
                            ->label('Fecha de Seguimiento')
                            ->required()
                            ->default(now())
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $date = \Carbon\Carbon::parse($state);
                                    $set('year', $date->year);
                                    $set('month', $date->month);
                                }
                            }),

                        Forms\Components\Hidden::make('year')
                            ->default(now()->year),

                        Forms\Components\Hidden::make('month')
                            ->default(now()->month),

                        Forms\Components\Select::make('status')
                            ->label('Estado del Seguimiento')
                            ->options([
                                'pending' => 'Pendiente',
                                'completed' => 'Completado',
                                'not_contacted' => 'No Contactado',
                                'refused' => 'Rechazado',
                            ])
                            ->default('completed')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Detalles del Seguimiento')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción del Seguimiento')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull()
                            ->placeholder('Describe las actividades, observaciones y resultados del seguimiento...'),

                        Forms\Components\DatePicker::make('next_followup')
                            ->label('Próximo Seguimiento')
                            ->nullable()
                            ->helperText('Fecha programada para el siguiente seguimiento'),

                        Forms\Components\TagsInput::make('actions_taken')
                            ->label('Acciones Realizadas')
                            ->placeholder('Presiona Enter para agregar cada acción')
                            ->columnSpanFull()
                            ->helperText('Ej: "Evaluación psicológica", "Terapia individual", "Remisión a especialista"'),

                        Forms\Components\Hidden::make('performed_by')
                            ->default(auth()->id()),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Información del paciente
                Tables\Columns\TextColumn::make('patient')
                    ->label('Paciente')
                    ->formatStateUsing(function ($record) {
                        if ($record->followupable && $record->followupable->patient) {
                            $patient = $record->followupable->patient;
                            return $patient->document_number . ' - ' . $patient->full_name;
                        }
                        return 'N/A';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHasMorph(
                            'followupable',
                            [MentalDisorder::class, SuicideAttempt::class, SubstanceConsumption::class],
                            function (Builder $q) use ($search) {
                                $q->whereHas('patient', function (Builder $patientQuery) use ($search) {
                                    $patientQuery->where('full_name', 'like', "%{$search}%")
                                        ->orWhere('document_number', 'like', "%{$search}%");
                                });
                            }
                        );
                    })
                    ->wrap(),

                // Tipo de caso
                Tables\Columns\BadgeColumn::make('case_type')
                    ->label('Tipo de Caso')
                    ->formatStateUsing(function ($record) {
                        return match ($record->followupable_type) {
                            MentalDisorder::class => 'Trastorno Mental',
                            SuicideAttempt::class => 'Intento Suicidio',
                            SubstanceConsumption::class => 'Consumo SPA',
                            default => 'Desconocido'
                        };
                    })
                    ->colors([
                        'primary' => MentalDisorder::class,
                        'danger' => SuicideAttempt::class,
                        'warning' => SubstanceConsumption::class,
                        'gray' => fn($state) => $state === 'Desconocido',
                    ])
                    ->icons([
                        'heroicon-o-heart' => MentalDisorder::class,
                        'heroicon-o-exclamation-triangle' => SuicideAttempt::class,
                        'heroicon-o-beaker' => SubstanceConsumption::class,
                    ]),

                // Detalles del caso
                Tables\Columns\TextColumn::make('case_details')
                    ->label('Detalles del Caso')
                    ->formatStateUsing(function ($record) {
                        if (!$record->followupable) return 'N/A';

                        return match ($record->followupable_type) {
                            MentalDisorder::class => ($record->followupable->diagnosis_code ?? 'Sin código') . ' - ' .
                                Str::limit($record->followupable->diagnosis_description ?? 'Sin descripción', 40),
                            SuicideAttempt::class =>
                            'Intento #' . ($record->followupable->attempt_number ?? '1') . ' - ' .
                                Str::limit($record->followupable->mechanism ?? 'Sin mecanismo', 40),
                            SubstanceConsumption::class =>
                            'Nivel: ' . ($record->followupable->consumption_level ?? 'N/A') . ' - ' .
                                Str::limit($record->followupable->diagnosis ?? 'Sin diagnóstico', 40),
                            default => 'N/A'
                        };
                    })
                    ->wrap()
                    ->tooltip(function ($record) {
                        if (!$record->followupable) return '';

                        return match ($record->followupable_type) {
                            MentalDisorder::class =>
                            "Código: " . ($record->followupable->diagnosis_code ?? 'N/A') . "\n" .
                                "Diagnóstico: " . ($record->followupable->diagnosis_description ?? 'N/A') . "\n" .
                                "Tipo ingreso: " . ($record->followupable->admission_type ?? 'N/A'),
                            SuicideAttempt::class =>
                            "Intentos: " . ($record->followupable->attempt_number ?? '1') . "\n" .
                                "Mecanismo: " . ($record->followupable->mechanism ?? 'N/A') . "\n" .
                                "Factor desencadenante: " . ($record->followupable->trigger_factor ?? 'N/A'),
                            SubstanceConsumption::class =>
                            "Sustancias: " . (is_array($record->followupable->substances_used)
                                ? implode(', ', $record->followupable->substances_used)
                                : 'N/A') . "\n" .
                                "Nivel consumo: " . ($record->followupable->consumption_level ?? 'N/A'),
                            default => ''
                        };
                    }),

                Tables\Columns\TextColumn::make('followup_date')
                    ->label('Fecha Seguimiento')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('year')
                    ->label('Año')
                    ->sortable(),

                Tables\Columns\TextColumn::make('month')
                    ->label('Mes')
                    ->sortable()
                    ->formatStateUsing(fn($state) => match ((int)$state) {
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
                        default => $state
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'pending',
                        'danger' => 'not_contacted',
                        'secondary' => 'refused',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'completed' => 'Completado',
                        'pending' => 'Pendiente',
                        'not_contacted' => 'No Contactado',
                        'refused' => 'Rechazado',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(50)
                    ->wrap()
                    ->tooltip(fn($record) => $record->description),

                Tables\Columns\TextColumn::make('actions_taken')
                    ->label('Acciones')
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            return implode(', ', $state);
                        }
                        return $state ?: 'Sin acciones';
                    })
                    ->limit(30)
                    ->tooltip(function ($record) {
                        if (is_array($record->actions_taken)) {
                            return "• " . implode("\n• ", $record->actions_taken);
                        }
                        return $record->actions_taken;
                    }),

                Tables\Columns\TextColumn::make('next_followup')
                    ->label('Próximo Seguimiento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(function ($state) {
                        if (!$state) return 'gray';
                        return $state < now() ? 'danger' : 'success';
                    })
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'No programado';
                        $color = $state < now() ? '🔴' : '🟢';
                        return $color . ' ' . $state->format('d/m/Y');
                    }),

                Tables\Columns\TextColumn::make('performed_by_name')
                    ->label('Registrado por')
                    ->formatStateUsing(function ($record) {
                        return $record->user ? $record->user->name : 'N/A';
                    })
                    ->sortable(false)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filtro por tipo de caso
                SelectFilter::make('followupable_type')
                    ->label('Tipo de Caso')
                    ->options([
                        MentalDisorder::class => 'Trastorno Mental',
                        SuicideAttempt::class => 'Intento Suicidio',
                        SubstanceConsumption::class => 'Consumo SPA',
                    ]),

                // Filtro por paciente
                SelectFilter::make('patient')
                    ->label('Paciente')
                    ->options(function () {
                        return Patient::all()->mapWithKeys(function ($patient) {
                            return [$patient->id => $patient->full_name . ' - ' . $patient->document_number];
                        });
                    })
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['value'], function (Builder $query) use ($data) {
                            $query->whereHasMorph(
                                'followupable',
                                [MentalDisorder::class, SuicideAttempt::class, SubstanceConsumption::class],
                                function (Builder $q) use ($data) {
                                    $q->where('patient_id', $data['value']);
                                }
                            );
                        });
                    }),

                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'completed' => 'Completado',
                        'pending' => 'Pendiente',
                        'not_contacted' => 'No Contactado',
                        'refused' => 'Rechazado',
                    ]),

                SelectFilter::make('year')
                    ->label('Año')
                    ->options([
                        2024 => '2024',
                        2025 => '2025',
                        2026 => '2026',
                    ])
                    ->default(2025),

                SelectFilter::make('month')
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
                    ]),

                Tables\Filters\Filter::make('overdue_followups')
                    ->label('Seguimientos Vencidos')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->where('next_followup', '<', now())
                            ->whereNotNull('next_followup')
                    ),

                Tables\Filters\Filter::make('recent')
                    ->label('Recientes (30 días)')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->where('followup_date', '>=', now()->subDays(30))
                    ),

                // Filtros por tipo específico
                Tables\Filters\Filter::make('mental_disorders_only')
                    ->label('Solo Trastornos Mentales')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->where('followupable_type', MentalDisorder::class)
                    ),

                Tables\Filters\Filter::make('suicide_attempts_only')
                    ->label('Solo Intentos Suicidio')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->where('followupable_type', SuicideAttempt::class)
                    ),

                Tables\Filters\Filter::make('substance_consumption_only')
                    ->label('Solo Consumo SPA')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->where('followupable_type', SubstanceConsumption::class)
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn() => auth()->user()->can('view_followups')),
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn($record) =>
                        auth()->user()->can('edit_all_followups') ||
                            (auth()->user()->can('edit_followups') && $record->created_by_id === auth()->id())
                    ),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()->can('delete_followups')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->can('delete_followups')),
                    Tables\Actions\ExportBulkAction::make()
                        ->visible(fn() => auth()->user()->can('export_followups')),
                ]),
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->visible(fn() => auth()->user()->can('export_followups')),
            ])
            ->defaultSort('followup_date', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonthlyFollowups::route('/'),
            'create' => Pages\CreateMonthlyFollowup::route('/create'),
            'edit' => Pages\EditMonthlyFollowup::route('/{record}/edit'),
        ];
    }

    // public static function getEloquentQuery(): Builder
    // {
    //     return parent::getEloquentQuery()
    //         ->withoutGlobalScopes([SoftDeletingScope::class])
    //         ->with([
    //             'followupable.patient', // Cargar tanto el caso como su paciente
    //             'user'
    //         ]);
    //     // ✅ REMOVIDO: ->where('followupable_type', Patient::class)
    //     // Ahora mostrará seguimientos de todos los tipos de casos
    // }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
        // ✅ REMOVIDO: ->where('followupable_type', Patient::class)
        // Ahora cuenta seguimientos pendientes de todos los tipos
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $pendingCount = static::getModel()::where('status', 'pending')->count();

        if ($pendingCount > 10) return 'danger';
        if ($pendingCount > 5) return 'warning';
        return 'primary';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_followups') ||
            auth()->user()->can('view_any_followups');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_followups');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Control de acceso basado en permisos
        if (auth()->user()->can('view_all_followups')) {
            // Puede ver todos los seguimientos
            return $query;
        } elseif (auth()->user()->can('view_any_followups')) {
            // Puede ver seguimientos relacionados con sus pacientes
            $query->whereHas('patient', function ($q) {
                $q->where('assigned_to', auth()->id());
            });
        } else {
            // Solo puede ver seguimientos creados por él
            $query->where('created_by_id', auth()->id());
        }

        return $query;
    }
}