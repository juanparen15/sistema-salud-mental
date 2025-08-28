<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MonthlyFollowupResource\Pages;
use App\Models\MonthlyFollowup;
use App\Models\Patient;
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
                Section::make('Información del Seguimiento')
                    ->schema([
                        Forms\Components\Select::make('followupable_id')
                            ->label('Paciente')
                            ->options(function () {
                                return Patient::all()->mapWithKeys(function ($patient) {
                                    return [$patient->id => $patient->full_name . ' - ' . $patient->document_number];
                                });
                            })
                            ->searchable()
                            ->required()
                            ->columnSpanFull()
                            ->afterStateUpdated(function ($state, $set) {
                                $set('followupable_type', Patient::class);
                            }),

                        Forms\Components\Hidden::make('followupable_type')
                            ->default(Patient::class),

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

                Section::make('Detalles del Seguimiento')
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
                Tables\Columns\TextColumn::make('followupable_id')
                    ->label('Doc. Paciente')
                    ->formatStateUsing(function ($record) {
                        if ($record->followupable_type === Patient::class && $record->followupable) {
                            return $record->followupable->document_number;
                        }
                        return 'N/A';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('followupable', function (Builder $query) use ($search) {
                            $query->where('document_number', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('followupable.full_name')
                    ->label('Paciente')
                    ->formatStateUsing(function ($record) {
                        if ($record->followupable_type === Patient::class && $record->followupable) {
                            return $record->followupable->full_name;
                        }
                        return 'N/A';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('followupable', function (Builder $query) use ($search) {
                            $query->where('full_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable()
                    ->wrap(),

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
                    ->tooltip(function ($record) {
                        return $record->description;
                    }),

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
                            return implode("\n• ", $record->actions_taken);
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

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Registrado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('followupable_id')
                    ->label('Paciente')
                    ->options(function () {
                        return Patient::all()->mapWithKeys(function ($patient) {
                            return [$patient->id => $patient->full_name . ' - ' . $patient->document_number];
                        });
                    })
                    ->searchable(),

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
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
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
            // 'view' => Pages\ViewMonthlyFollowup::route('/{record}'),
            'edit' => Pages\EditMonthlyFollowup::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['followupable', 'user']) // Usar followupable en lugar de patient
            ->where('followupable_type', Patient::class); // Filtro para solo pacientes
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')
            ->where('followupable_type', Patient::class)
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $pendingCount = static::getModel()::where('status', 'pending')
            ->where('followupable_type', Patient::class)
            ->count();

        if ($pendingCount > 10) return 'danger';
        if ($pendingCount > 5) return 'warning';
        return 'primary';
    }
}
