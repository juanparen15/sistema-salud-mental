<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MentalDisorderResource\Pages;
use App\Models\MentalDisorder;
use App\Models\Patient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MentalDisorderResource extends Resource
{
    protected static ?string $model = MentalDisorder::class;
    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationLabel = 'Trastornos Mentales';
    protected static ?string $modelLabel = 'Trastorno Mental';
    protected static ?string $pluralModelLabel = 'Trastornos Mentales';
    protected static ?int $navigationSort = 2;
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
                            ])
                            ->preload(),
                    ]),

                Forms\Components\Section::make('Información de Ingreso')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('admission_date')
                                    ->label('Fecha de Ingreso')
                                    ->required()
                                    ->default(now()),

                                Forms\Components\Select::make('admission_type')
                                    ->label('Tipo de Ingreso')
                                    ->options([
                                        'AMBULATORIO' => 'Ambulatorio',
                                        'HOSPITALARIO' => 'Hospitalario',
                                        'URGENCIAS' => 'Urgencias',
                                    ])
                                    ->required(),

                                Forms\Components\Select::make('admission_via')
                                    ->label('Ingreso Por')
                                    ->options([
                                        'URGENCIAS' => 'Urgencias',
                                        'CONSULTA_EXTERNA' => 'Consulta Externa',
                                        'HOSPITALIZACION' => 'Hospitalización',
                                        'REFERENCIA' => 'Referencia',
                                    ])
                                    ->required(),

                                Forms\Components\TextInput::make('service_area')
                                    ->label('Área/Servicio de Atención')
                                    ->maxLength(255),
                            ]),
                    ]),

                Forms\Components\Section::make('Diagnóstico')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('diagnosis_code')
                                    ->label('Código CIE-10')
                                    ->required()
                                    ->maxLength(10),

                                Forms\Components\TextInput::make('diagnosis_description')
                                    ->label('Descripción del Diagnóstico')
                                    ->required()
                                    ->maxLength(500),

                                Forms\Components\DateTimePicker::make('diagnosis_date')
                                    ->label('Fecha del Diagnóstico')
                                    ->required(),

                                Forms\Components\Select::make('diagnosis_type')
                                    ->label('Tipo de Diagnóstico')
                                    ->options([
                                        'Diagnostico Principal' => 'Diagnóstico Principal',
                                        'Diagnostico Relacionado' => 'Diagnóstico Relacionado',
                                    ])
                                    ->required(),

                                Forms\Components\Select::make('status')
                                    ->label('Estado')
                                    ->options([
                                        'active' => 'Activo',
                                        'inactive' => 'Inactivo',
                                        'discharged' => 'Dado de Alta',
                                    ])
                                    ->default('active')
                                    ->required(),
                            ]),

                        Forms\Components\Textarea::make('additional_observation')
                            ->label('Observaciones Adicionales')
                            ->rows(3)
                            ->maxLength(1000),
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

                Tables\Columns\TextColumn::make('diagnosis_code')
                    ->label('CIE-10')
                    ->searchable(),

                Tables\Columns\TextColumn::make('diagnosis_description')
                    ->label('Diagnóstico')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->diagnosis_description),

                Tables\Columns\TextColumn::make('admission_date')
                    ->label('F. Ingreso')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('admission_type')
                    ->label('Tipo Ingreso')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'AMBULATORIO' => 'success',
                        'HOSPITALARIO' => 'warning',
                        'URGENCIAS' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'discharged' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('followups_count')
                    ->label('Seguimientos')
                    ->counts('followups')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        'discharged' => 'Dado de Alta',
                    ]),

                Tables\Filters\SelectFilter::make('admission_type')
                    ->label('Tipo Ingreso')
                    ->options([
                        'AMBULATORIO' => 'Ambulatorio',
                        'HOSPITALARIO' => 'Hospitalario',
                        'URGENCIAS' => 'Urgencias',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('add_followup')
                    ->label('Añadir Seguimiento')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->url(fn($record) => route('filament.admin.resources.monthly-followups.create', [
                        'type' => 'mental_disorder',
                        'id' => $record->id
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMentalDisorders::route('/'),
            'create' => Pages\CreateMentalDisorder::route('/create'),
            'edit' => Pages\EditMentalDisorder::route('/{record}/edit'),
            // 'view' => Pages\ViewMentalDisorder::route('/{record}'),
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
