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
                        Forms\Components\Select::make('patient_id')
                            ->label('Paciente')
                            ->relationship('patient', 'full_name')
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->full_name} - {$record->identification_number}")
                            ->searchable(['first_name', 'last_name', 'identification_number'])
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('followup_date')
                            ->label('Fecha de Seguimiento')
                            ->required()
                            ->default(now()),

                        Forms\Components\Select::make('mood_state')
                            ->label('Estado de Ánimo')
                            ->options([
                                'Muy Bueno' => 'Muy Bueno',
                                'Bueno' => 'Bueno',
                                'Regular' => 'Regular',
                                'Malo' => 'Malo',
                                'Muy Malo' => 'Muy Malo',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Evaluación de Riesgos')
                    ->schema([
                        Forms\Components\Toggle::make('suicide_risk')
                            ->label('Riesgo de Suicidio'),

                        Forms\Components\Toggle::make('suicide_attempt')
                            ->label('Intento de Suicidio')
                            ->reactive(),

                        Forms\Components\DatePicker::make('suicide_attempt_date')
                            ->label('Fecha del Intento')
                            ->visible(fn(Forms\Get $get) => $get('suicide_attempt')),

                        Forms\Components\TextInput::make('suicide_method')
                            ->label('Método Utilizado')
                            ->visible(fn(Forms\Get $get) => $get('suicide_attempt'))
                            ->maxLength(255),

                        Forms\Components\Toggle::make('substance_use')
                            ->label('Consumo de Sustancias')
                            ->reactive(),

                        Forms\Components\Select::make('substance_type')
                            ->label('Tipo de Sustancia')
                            ->options([
                                'Alcohol' => 'Alcohol',
                                'Marihuana' => 'Marihuana',
                                'Cocaína' => 'Cocaína',
                                'Basuco' => 'Basuco',
                                'Heroína' => 'Heroína',
                                'Medicamentos' => 'Medicamentos',
                                'Otras' => 'Otras',
                            ])
                            ->visible(fn(Forms\Get $get) => $get('substance_use')),

                        Forms\Components\Select::make('consumption_frequency')
                            ->label('Frecuencia de Consumo')
                            ->options([
                                'Diario' => 'Diario',
                                'Semanal' => 'Semanal',
                                'Quincenal' => 'Quincenal',
                                'Mensual' => 'Mensual',
                                'Ocasional' => 'Ocasional',
                            ])
                            ->visible(fn(Forms\Get $get) => $get('substance_use')),

                        Forms\Components\TextInput::make('consumption_duration')
                            ->label('Duración del Consumo')
                            ->visible(fn(Forms\Get $get) => $get('substance_use'))
                            ->placeholder('Ej: 2 años, 6 meses')
                            ->maxLength(100),

                        Forms\Components\Select::make('impact_level')
                            ->label('Nivel de Impacto')
                            ->options([
                                'Leve' => 'Leve',
                                'Moderado' => 'Moderado',
                                'Severo' => 'Severo',
                            ])
                            ->visible(fn(Forms\Get $get) => $get('substance_use')),

                        Forms\Components\Toggle::make('violence_risk')
                            ->label('Riesgo de Violencia'),
                    ])
                    ->columns(2),

                Section::make('Intervención y Seguimiento')
                    ->schema([
                        Forms\Components\Toggle::make('intervention_provided')
                            ->label('Intervención Realizada'),

                        Forms\Components\Toggle::make('referral_made')
                            ->label('Remisión Realizada')
                            ->reactive(),

                        Forms\Components\TextInput::make('referral_institution')
                            ->label('Institución de Remisión')
                            ->visible(fn(Forms\Get $get) => $get('referral_made'))
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('next_appointment')
                            ->label('Próxima Cita'),

                        Forms\Components\Textarea::make('observations')
                            ->label('Observaciones')
                            ->columnSpanFull()
                            ->rows(3),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('patient.document_number')
                    ->label('Identificación')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('patient.full_name')
                    ->label('Paciente')
                    ->getStateUsing(fn($record) => "{$record->patient->full_name}")
                    ->searchable(['patient.full_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('followup_date')
                    ->label('Fecha de Seguimiento')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('mood_state')
                    ->label('Estado de Ánimo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Muy Bueno' => 'success',
                        'Bueno' => 'primary',
                        'Regular' => 'warning',
                        'Malo' => 'danger',
                        'Muy Malo' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('suicide_risk')
                    ->label('Riesgo Suicidio')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),

                Tables\Columns\IconColumn::make('suicide_attempt')
                    ->label('Intento')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-mark')
                    ->falseIcon('heroicon-o-check')
                    ->trueColor('danger')
                    ->falseColor('success'),

                Tables\Columns\IconColumn::make('substance_use')
                    ->label('Sustancias')
                    ->boolean()
                    ->trueIcon('heroicon-o-beaker')
                    ->falseIcon('heroicon-o-check')
                    ->trueColor('warning')
                    ->falseColor('success'),

                // Reemplazando TagsColumn deprecado con TextColumn personalizada
                Tables\Columns\TextColumn::make('risk_indicators')
                    ->label('Indicadores')
                    ->getStateUsing(function ($record) {
                        $indicators = [];
                        if ($record->suicide_risk) $indicators[] = 'Riesgo Suicidio';
                        if ($record->suicide_attempt) $indicators[] = 'Intento';
                        if ($record->substance_use) $indicators[] = 'Sustancias';
                        if ($record->violence_risk) $indicators[] = 'Violencia';
                        return implode(', ', $indicators) ?: 'Ninguno';
                    })
                    ->wrap(),

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
                SelectFilter::make('patient')
                    ->relationship('patient', 'full_name')
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->full_name}")
                    ->searchable()
                    ->preload(),

                SelectFilter::make('mood_state')
                    ->label('Estado de Ánimo')
                    ->options([
                        'Muy Bueno' => 'Muy Bueno',
                        'Bueno' => 'Bueno',
                        'Regular' => 'Regular',
                        'Malo' => 'Malo',
                        'Muy Malo' => 'Muy Malo',
                    ]),

                Tables\Filters\Filter::make('suicide_risk')
                    ->label('Con Riesgo de Suicidio')
                    ->query(fn(Builder $query): Builder => $query->where('suicide_risk', true)),

                Tables\Filters\Filter::make('suicide_attempt')
                    ->label('Con Intento de Suicidio')
                    ->query(fn(Builder $query): Builder => $query->where('suicide_attempt', true)),

                Tables\Filters\Filter::make('substance_use')
                    ->label('Con Consumo de Sustancias')
                    ->query(fn(Builder $query): Builder => $query->where('substance_use', true)),
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
            ->paginated([10, 25, 50, 100]); // Reemplazando limit() deprecado
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
            ->with(['patient', 'user']);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('followup_date', today())->count();
    }

    // public static function getNavigationBadge(): ?string
    // {
    //     return static::getModel()::where('status', 'pending')
    //         ->where('followup_date', '<=', now())
    //         ->count();
    // }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
