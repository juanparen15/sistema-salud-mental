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
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Seguimiento')
                    ->schema([
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

                        Forms\Components\Select::make('followupable_id')
                            ->label('Caso Específico')
                            ->options(function (callable $get) {
                                $type = $get('followupable_type');
                                if (!$type) return [];

                                // ✅ Filtrar opciones según permisos
                                $query = match ($type) {
                                    MentalDisorder::class => MentalDisorder::with('patient'),
                                    SuicideAttempt::class => SuicideAttempt::with('patient'),
                                    SubstanceConsumption::class => SubstanceConsumption::with('patient'),
                                    default => null
                                };

                                if (!$query) return [];

                                // Aplicar filtros de permisos
                                if (!auth()->user()->can('view_any_patients')) {
                                    $query->whereHas('patient', function ($q) {
                                        $q->where('assigned_to', auth()->id());
                                    });
                                }

                                return $query->get()->mapWithKeys(fn($case) => [
                                    $case->id => $case->patient->full_name . ' - ' . $case->patient->document_number
                                ]);
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
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('followup_date')
                    ->label('Fecha Seguimiento')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('year')
                    ->label('Año')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

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
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('next_followup')
                    ->label('Próximo')
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
                SelectFilter::make('followupable_type')
                    ->label('Tipo de Caso')
                    ->options([
                        MentalDisorder::class => 'Trastorno Mental',
                        SuicideAttempt::class => 'Intento Suicidio',
                        SubstanceConsumption::class => 'Consumo SPA',
                    ]),

                SelectFilter::make('patient')
                    ->label('Paciente')
                    ->options(function () {
                        $query = Patient::query();

                        // ✅ Filtrar pacientes según permisos
                        if (!auth()->user()->can('view_any_patients')) {
                            $query->where('assigned_to', auth()->id());
                        }

                        return $query->get()->mapWithKeys(function ($patient) {
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

                Tables\Filters\Filter::make('my_followups')
                    ->label('Mis Seguimientos')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->where('performed_by', auth()->id())
                    )
                    ->visible(fn() => auth()->user()->can('view_all_followups')), // Solo visible si puede ver todos
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn() => auth()->user()->can('view_followups')),
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn($record) =>
                        auth()->user()->can('edit_all_followups') ||
                            (auth()->user()->can('edit_followups') && $record->performed_by === auth()->id())
                    ),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()->can('delete_followups')),

                // ✅ Acción para programar próximo seguimiento
                Tables\Actions\Action::make('schedule_next')
                    ->label('Programar Próximo')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->form([
                        Forms\Components\DatePicker::make('next_followup')
                            ->label('Fecha del Próximo Seguimiento')
                            ->required()
                            ->minDate(now()),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update(['next_followup' => $data['next_followup']]);

                        Notification::make()
                            ->title('Próximo seguimiento programado')
                            ->success()
                            ->send();
                    })
                    ->visible(fn() => auth()->user()->can('edit_followups')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->can('delete_followups')),
                    Tables\Actions\ExportBulkAction::make()
                        ->visible(fn() => auth()->user()->can('export_followups')),

                    // ✅ Acción masiva para cambiar estado
                    Tables\Actions\BulkAction::make('change_status')
                        ->label('Cambiar Estado')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Nuevo Estado')
                                ->options([
                                    'pending' => 'Pendiente',
                                    'completed' => 'Completado',
                                    'not_contacted' => 'No Contactado',
                                    'refused' => 'Rechazado',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data, $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                // Solo permitir editar si tiene permisos
                                if (
                                    auth()->user()->can('edit_all_followups') ||
                                    (auth()->user()->can('edit_followups') && $record->performed_by === auth()->id())
                                ) {
                                    $record->update(['status' => $data['status']]);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("Estado actualizado en {$count} seguimientos")
                                ->success()
                                ->send();
                        })
                        ->visible(fn() => auth()->user()->can('edit_followups')),
                ]),
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->visible(fn() => auth()->user()->can('export_followups')),

                // ✅ Acción para crear seguimiento rápido
                Tables\Actions\Action::make('quick_create')
                    ->label('Seguimiento Rápido')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('patient_id')
                            ->label('Paciente')
                            ->options(function () {
                                $query = Patient::query();

                                if (!auth()->user()->can('view_any_patients')) {
                                    $query->where('assigned_to', auth()->id());
                                }

                                return $query->get()->mapWithKeys(fn($patient) => [
                                    $patient->id => $patient->full_name . ' - ' . $patient->document_number
                                ]);
                            })
                            ->searchable()
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción Rápida')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data) {
                        MonthlyFollowup::create([
                            'followupable_type' => Patient::class,
                            'followupable_id' => $data['patient_id'],
                            'followup_date' => now(),
                            'year' => now()->year,
                            'month' => now()->month,
                            'status' => 'completed',
                            'description' => $data['description'],
                            'performed_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Seguimiento rápido creado')
                            ->success()
                            ->send();
                    })
                    ->visible(fn() => auth()->user()->can('create_followups')),
            ])
            ->defaultSort('followup_date', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    // ✅ Filtrar registros según permisos del usuario
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'followupable.patient',
                'user'
            ]);

        // Control de acceso basado en permisos
        if (auth()->user()->can('view_all_followups')) {
            // Puede ver todos los seguimientos
            return $query;
        } elseif (auth()->user()->can('view_any_followups')) {
            // Puede ver seguimientos relacionados con sus pacientes asignados
            $query->whereHasMorph(
                'followupable',
                [MentalDisorder::class, SuicideAttempt::class, SubstanceConsumption::class],
                function (Builder $q) {
                    $q->whereHas('patient', function (Builder $patientQuery) {
                        $patientQuery->where('assigned_to', auth()->id());
                    });
                }
            );
        } else {
            // Solo puede ver seguimientos creados por él
            $query->where('performed_by', auth()->id());
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonthlyFollowups::route('/'),
            'create' => Pages\CreateMonthlyFollowup::route('/create'),
            'edit' => Pages\EditMonthlyFollowup::route('/{record}/edit'),
            'view' => Pages\ViewMonthlyFollowup::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::where('status', 'pending');

        // ✅ Aplicar filtros de permisos también al badge
        if (!auth()->user()->can('view_all_followups')) {
            if (auth()->user()->can('view_any_followups')) {
                $query->whereHasMorph(
                    'followupable',
                    [MentalDisorder::class, SuicideAttempt::class, SubstanceConsumption::class],
                    function (Builder $q) {
                        $q->whereHas('patient', function (Builder $patientQuery) {
                            $patientQuery->where('assigned_to', auth()->id());
                        });
                    }
                );
            } else {
                $query->where('performed_by', auth()->id());
            }
        }

        return $query->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $pendingCount = static::getNavigationBadge();

        if ($pendingCount > 10) return 'danger';
        if ($pendingCount > 5) return 'warning';
        return 'primary';
    }

    public static function canViewAny(): bool
    {
        if (!auth()->check()) return false;

        return auth()->user()->can('view_followups') ||
            auth()->user()->can('view_any_followups') ||
            auth()->user()->can('view_all_followups');
    }

    public static function canCreate(): bool
    {
        if (!auth()->check()) return false;

        return auth()->user()->can('create_followups');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }
}
