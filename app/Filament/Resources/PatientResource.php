<?php

// namespace App\Filament\Resources;

// use App\Filament\Resources\PatientResource\Pages;
// use App\Models\Patient;
// use Filament\Forms;
// use Filament\Forms\Form;
// use Filament\Resources\Resource;
// use Filament\Tables;
// use Filament\Tables\Table;
// use Illuminate\Database\Eloquent\Builder;

// class PatientResource extends Resource
// {
//     protected static ?string $model = Patient::class;
//     protected static ?string $navigationIcon = 'heroicon-o-user-group';
//     protected static ?string $navigationLabel = 'Pacientes';
//     protected static ?string $modelLabel = 'Paciente';
//     protected static ?string $pluralModelLabel = 'Pacientes';
//     protected static ?int $navigationSort = 1;

//     public static function form(Form $form): Form
//     {
//         return $form
//             ->schema([
//                 Forms\Components\Section::make('Información Personal')
//                     ->schema([
//                         Forms\Components\Grid::make(2)
//                             ->schema([
//                                 Forms\Components\Select::make('document_type')
//                                     ->label('Tipo de Documento')
//                                     ->options([
//                                         'CC' => 'Cédula de Ciudadanía',
//                                         'TI' => 'Tarjeta de Identidad',
//                                         'CE' => 'Cédula de Extranjería',
//                                         'PA' => 'Pasaporte',
//                                         'RC' => 'Registro Civil',
//                                     ])
//                                     ->required(),

//                                 Forms\Components\TextInput::make('document_number')
//                                     ->label('Número de Documento')
//                                     ->required()
//                                     ->unique(ignoreRecord: true)
//                                     ->maxLength(20),

//                                 Forms\Components\TextInput::make('full_name')
//                                     ->label('Nombres y Apellidos')
//                                     ->required()
//                                     ->maxLength(255),

//                                 Forms\Components\Select::make('gender')
//                                     ->label('Sexo')
//                                     ->options([
//                                         'Masculino' => 'Masculino',
//                                         'Femenino' => 'Femenino',
//                                         'Otro' => 'Otro',
//                                     ])
//                                     ->required(),

//                                 Forms\Components\DatePicker::make('birth_date')
//                                     ->label('Fecha de Nacimiento')
//                                     ->required()
//                                     ->maxDate(now())
//                                     ->displayFormat('d/m/Y'),

//                                 Forms\Components\TextInput::make('phone')
//                                     ->label('Teléfono')
//                                     ->tel()
//                                     ->maxLength(20),
//                             ]),
//                     ]),

//                 Forms\Components\Section::make('Información de Ubicación')
//                     ->schema([
//                         Forms\Components\Grid::make(2)
//                             ->schema([
//                                 Forms\Components\TextInput::make('address')
//                                     ->label('Dirección')
//                                     ->maxLength(255),

//                                 Forms\Components\TextInput::make('neighborhood')
//                                     ->label('Barrio')
//                                     ->maxLength(100),

//                                 Forms\Components\TextInput::make('village')
//                                     ->label('Vereda')
//                                     ->maxLength(100),
//                             ]),
//                     ]),

//                 Forms\Components\Section::make('Información de EPS')
//                     ->schema([
//                         Forms\Components\Grid::make(2)
//                             ->schema([
//                                 Forms\Components\TextInput::make('eps_code')
//                                     ->label('Código EPS')
//                                     ->maxLength(50),

//                                 Forms\Components\TextInput::make('eps_name')
//                                     ->label('Nombre EPS')
//                                     ->maxLength(255),
//                             ]),

//                         Forms\Components\Select::make('status')
//                             ->label('Estado')
//                             ->options([
//                                 'active' => 'Activo',
//                                 'inactive' => 'Inactivo',
//                                 'discharged' => 'Dado de Alta',
//                             ])
//                             ->default('active')
//                             ->required(),
//                     ]),
//             ]);
//     }

//     public static function table(Table $table): Table
//     {
//         return $table
//             ->columns([
//                 Tables\Columns\TextColumn::make('document_number')
//                     ->label('Documento')
//                     ->searchable()
//                     ->sortable(),

//                 Tables\Columns\TextColumn::make('full_name')
//                     ->label('Nombre Completo')
//                     ->searchable()
//                     ->sortable(),

//                 Tables\Columns\TextColumn::make('age')
//                     ->label('Edad')
//                     ->sortable(),

//                 Tables\Columns\TextColumn::make('gender')
//                     ->label('Sexo')
//                     ->badge()
//                     ->color(fn(string $state): string => match ($state) {
//                         'Masculino' => 'info',
//                         'Femenino' => 'danger',
//                         default => 'gray',
//                     }),

//                 Tables\Columns\TextColumn::make('phone')
//                     ->label('Teléfono')
//                     ->searchable(),

//                 Tables\Columns\TagsColumn::make('active_conditions')
//                     ->label('Condiciones Activas')
//                     ->separator(','),

//                 // Tables\Columns\TextColumn::make('status')
//                 //     ->label('Estado')
//                 //     ->badge()
//                 //     ->color(fn(string $state): string => match ($state) {
//                 //         'active' => 'success',
//                 //         'inactive' => 'warning',
//                 //         'discharged' => 'info',
//                 //         default => 'gray',
//                 //     })
//                 //     ->sortable(),

//                 Tables\Columns\TextColumn::make('created_at')
//                     ->label('Fecha Registro')
//                     ->dateTime('d/m/Y')
//                     ->sortable()
//                     ->toggleable(isToggledHiddenByDefault: true),
//             ])
//             ->filters([
//                 Tables\Filters\SelectFilter::make('status')
//                     ->label('Estado')
//                     ->options([
//                         'active' => 'Activo',
//                         'inactive' => 'Inactivo',
//                         'discharged' => 'Dado de Alta',
//                     ]),
//                 Tables\Filters\SelectFilter::make('gender')
//                     ->label('Sexo')
//                     ->options([
//                         'Masculino' => 'Masculino',
//                         'Femenino' => 'Femenino',
//                         'Otro' => 'Otro',
//                     ]),

//                 Tables\Filters\Filter::make('has_mental_disorder')
//                     ->label('Con Trastorno Mental')
//                     ->query(fn(Builder $query): Builder => $query->whereHas('mentalDisorders')),

//                 Tables\Filters\Filter::make('has_suicide_attempt')
//                     ->label('Con Intento Suicidio')
//                     ->query(fn(Builder $query): Builder => $query->whereHas('suicideAttempts')),

//                 Tables\Filters\Filter::make('has_substance_consumption')
//                     ->label('Con Consumo SPA')
//                     ->query(fn(Builder $query): Builder => $query->whereHas('substanceConsumptions')),
//             ])
//             ->actions([
//                 Tables\Actions\ViewAction::make(),
//                 Tables\Actions\EditAction::make(),
//             ])
//             ->bulkActions([
//                 Tables\Actions\BulkActionGroup::make([
//                     Tables\Actions\DeleteBulkAction::make(),
//                 ]),
//             ]);
//     }

//     public static function getRelations(): array
//     {
//         return [
//             //
//         ];
//     }

//     public static function getPages(): array
//     {
//         return [
//             'index' => Pages\ListPatients::route('/'),
//             'create' => Pages\CreatePatient::route('/create'),
//             'edit' => Pages\EditPatient::route('/{record}/edit'),
//             // 'view' => Pages\ViewPatient::route('/{record}'),
//         ];
//     }

//     public static function getGloballySearchableAttributes(): array
//     {
//         return ['document_number', 'full_name', 'phone'];
//     }

//     public static function getNavigationBadge(): ?string
//     {
//         return static::getModel()::where('status', 'active')->count();
//     }

//     public static function getNavigationBadgeColor(): ?string
//     {
//         return 'success';
//     }
// }

namespace App\Filament\Resources;

use App\Filament\Resources\PatientResource\Pages;
use App\Models\Patient;
use App\Imports\PatientsImport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Pacientes';

    protected static ?string $modelLabel = 'Paciente';

    protected static ?string $pluralModelLabel = 'Pacientes';

    protected static ?string $navigationGroup = 'Gestión de Pacientes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Personal')
                    ->schema([
                        Forms\Components\TextInput::make('document_number')
                            ->label('Número de Documento')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true),
                        
                        Forms\Components\Select::make('document_type')
                            ->label('Tipo de Documento')
                            ->options([
                                'CC' => 'Cédula de Ciudadanía',
                                'TI' => 'Tarjeta de Identidad',
                                'CE' => 'Cédula de Extranjería',
                                'PA' => 'Pasaporte',
                                'RC' => 'Registro Civil',
                                'MS' => 'Menor sin Identificación',
                                'AS' => 'Adulto sin Identificación',
                                'CN' => 'Certificado de Nacido Vivo',
                            ])
                            ->required(),
                        
                        Forms\Components\TextInput::make('full_name')
                            ->label('Nombre Completo')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Select::make('gender')
                            ->label('Género')
                            ->options([
                                'Masculino' => 'Masculino',
                                'Femenino' => 'Femenino',
                                'Otro' => 'Otro',
                            ])
                            ->required(),
                        
                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Fecha de Nacimiento')
                            ->required()
                            ->maxDate(now()),
                        
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'active' => 'Activo',
                                'inactive' => 'Inactivo',
                                'discharged' => 'Dado de Alta',
                            ])
                            ->default('active')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Información de Contacto')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(20),
                        
                        Forms\Components\Textarea::make('address')
                            ->label('Dirección')
                            ->maxLength(255)
                            ->rows(2),
                        
                        Forms\Components\TextInput::make('neighborhood')
                            ->label('Barrio')
                            ->maxLength(100),
                        
                        Forms\Components\TextInput::make('village')
                            ->label('Vereda')
                            ->maxLength(100),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Información EPS')
                    ->schema([
                        Forms\Components\TextInput::make('eps_code')
                            ->label('Código EPS')
                            ->maxLength(50),
                        
                        Forms\Components\TextInput::make('eps_name')
                            ->label('Nombre EPS')
                            ->maxLength(100),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('document_number')
                    ->label('Documento')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('document_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'CC' => 'success',
                        'TI' => 'info',
                        'CE' => 'warning',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nombre Completo')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('age')
                    ->label('Edad')
                    ->getStateUsing(fn ($record) => $record->age)
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('gender')
                    ->label('Género')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Masculino' => 'blue',
                        'Femenino' => 'pink',
                        'Otro' => 'gray',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('neighborhood')
                    ->label('Barrio')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('eps_name')
                    ->label('EPS')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('followups_count')
                    ->label('Seguimientos')
                    ->getStateUsing(fn ($record) => $record->monthlyFollowups()->count())
                    ->badge()
                    ->color('success'),
                
                Tables\Columns\TextColumn::make('latest_followup')
                    ->label('Último Seguimiento')
                    ->getStateUsing(function ($record) {
                        $latest = $record->monthlyFollowups()->latest('followup_date')->first();
                        return $latest ? $latest->followup_date->format('d/m/Y') : 'Sin seguimiento';
                    })
                    ->color(fn ($state) => $state === 'Sin seguimiento' ? 'warning' : 'success'),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'discharged' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        'discharged' => 'Dado de Alta',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('document_type')
                    ->label('Tipo de Documento')
                    ->options([
                        'CC' => 'Cédula de Ciudadanía',
                        'TI' => 'Tarjeta de Identidad',
                        'CE' => 'Cédula de Extranjería',
                        'PA' => 'Pasaporte',
                        'RC' => 'Registro Civil',
                    ]),
                
                Tables\Filters\SelectFilter::make('gender')
                    ->label('Género')
                    ->options([
                        'Masculino' => 'Masculino',
                        'Femenino' => 'Femenino',
                        'Otro' => 'Otro',
                    ]),
                
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        'discharged' => 'Dado de Alta',
                    ]),
                
                Tables\Filters\Filter::make('age_range')
                    ->label('Rango de Edad')
                    ->form([
                        Forms\Components\TextInput::make('age_from')
                            ->label('Edad mínima')
                            ->numeric(),
                        Forms\Components\TextInput::make('age_to')
                            ->label('Edad máxima')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['age_from'], function ($query) use ($data) {
                                $minDate = now()->subYears($data['age_from'] + 1)->addDay();
                                return $query->where('birth_date', '<=', $minDate);
                            })
                            ->when($data['age_to'], function ($query) use ($data) {
                                $maxDate = now()->subYears($data['age_to']);
                                return $query->where('birth_date', '>=', $maxDate);
                            });
                    }),
                
                Tables\Filters\Filter::make('with_recent_followup')
                    ->label('Con seguimiento reciente')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereHas('monthlyFollowups', fn ($query) => 
                            $query->where('followup_date', '>=', now()->subDays(30))
                        )
                    ),
                
                Tables\Filters\Filter::make('without_recent_followup')
                    ->label('Sin seguimiento reciente')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereDoesntHave('monthlyFollowups', fn ($query) => 
                            $query->where('followup_date', '>=', now()->subDays(45))
                        )
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPatients::route('/'),
            'create' => Pages\CreatePatient::route('/create'),
            'edit' => Pages\EditPatient::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['monthlyFollowups']);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}