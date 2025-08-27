<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubstanceConsumptionResource\Pages;
use App\Models\SubstanceConsumption;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubstanceConsumptionResource extends Resource
{
    protected static ?string $model = SubstanceConsumption::class;
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Consumos de SPA';
    protected static ?string $modelLabel = 'Consumo de SPA';
    protected static ?string $pluralModelLabel = 'Consumos de SPA';
    protected static ?int $navigationSort = 4;
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
                                    ]),
                            ]),
                    ]),

                Forms\Components\Section::make('Información del Consumo')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('admission_date')
                                    ->label('Fecha de Ingreso')
                                    ->required()
                                    ->default(now()),

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

                                Forms\Components\TextInput::make('diagnosis')
                                    ->label('Diagnóstico')
                                    ->required()
                                    ->maxLength(500)
                                    ->columnSpan(2),

                                Forms\Components\TagsInput::make('substances_used')
                                    ->label('Sustancias Consumidas')
                                    ->placeholder('Agregue sustancias')
                                    ->suggestions([
                                        'Alcohol',
                                        'Marihuana',
                                        'Cocaína',
                                        'Basuco',
                                        'Heroína',
                                        'Éxtasis',
                                        'LSD',
                                        'Inhalantes',
                                        'Benzodiacepinas',
                                        'Anfetaminas',
                                        'Metanfetaminas',
                                        'Opioides',
                                        'Tabaco',
                                        'Múltiples drogas',
                                        'Otros',
                                    ])
                                    ->required(),

                                Forms\Components\Select::make('consumption_level')
                                    ->label('Nivel de Consumo')
                                    ->options([
                                        'Alto Riesgo' => 'Alto Riesgo',
                                        'Riesgo Moderado' => 'Riesgo Moderado',
                                        'Bajo Riesgo' => 'Bajo Riesgo',
                                        'Perjudicial' => 'Perjudicial',
                                    ])
                                    ->required(),

                                Forms\Components\Select::make('status')
                                    ->label('Estado')
                                    ->options([
                                        'active' => 'Activo',
                                        'inactive' => 'Inactivo',
                                        'in_treatment' => 'En Tratamiento',
                                        'recovered' => 'Recuperado',
                                    ])
                                    ->default('active')
                                    ->required(),
                            ]),

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

                Tables\Columns\TextColumn::make('admission_date')
                    ->label('F. Ingreso')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                Tables\Columns\TagsColumn::make('substances_used')
                    ->label('Sustancias')
                    ->limit(3),

                Tables\Columns\TextColumn::make('consumption_level')
                    ->label('Nivel')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Alto Riesgo' => 'danger',
                        'Perjudicial' => 'danger',
                        'Riesgo Moderado' => 'warning',
                        'Bajo Riesgo' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('diagnosis')
                    ->label('Diagnóstico')
                    ->limit(40)
                    ->tooltip(fn($record) => $record->diagnosis),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'warning',
                        'inactive' => 'gray',
                        'in_treatment' => 'info',
                        'recovered' => 'success',
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
                        'in_treatment' => 'En Tratamiento',
                        'recovered' => 'Recuperado',
                    ]),

                Tables\Filters\SelectFilter::make('consumption_level')
                    ->label('Nivel de Consumo')
                    ->options([
                        'Alto Riesgo' => 'Alto Riesgo',
                        'Riesgo Moderado' => 'Riesgo Moderado',
                        'Bajo Riesgo' => 'Bajo Riesgo',
                        'Perjudicial' => 'Perjudicial',
                    ]),

                Tables\Filters\Filter::make('multiple_substances')
                    ->label('Múltiples Sustancias')
                    ->query(function (Builder $query): Builder {
                        return $query->whereJsonLength('substances_used', '>', 1);
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('add_followup')
                    ->label('Seguimiento')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('success'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('admission_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubstanceConsumptions::route('/'),
            'create' => Pages\CreateSubstanceConsumption::route('/create'),
            'edit' => Pages\EditSubstanceConsumption::route('/{record}/edit'),
            // 'view' => Pages\ViewSubstanceConsumption::route('/{record}'),
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
}
