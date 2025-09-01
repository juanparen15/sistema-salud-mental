<?php

namespace App\Filament\Resources\MonthlyFollowupResource\Pages;

use App\Filament\Resources\MonthlyFollowupResource;
use App\Models\MonthlyFollowup;
use App\Models\Patient;
use App\Models\MentalDisorder;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Models\SuicideAttempt;
use App\Models\SubstanceConsumption;


class CreateMonthlyFollowup extends CreateRecord
{
    protected static string $resource = MonthlyFollowupResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    // public function getTitle(): string
    // {
    //     $sourceType = request()->query('source_type');
    //     $patientId = request()->query('patient_id');

    //     if ($sourceType === 'mental_disorder' && $patientId) {
    //         $patient = Patient::find($patientId);
    //         if ($patient) {
    //             return "Nuevo Seguimiento para {$patient->full_name}";
    //         }
    //     }

    //     return 'Nuevo Seguimiento Mensual';
    // }

    public function getTitle(): string
    {
        $sourceType = request()->query('source_type');
        $patientId = request()->query('patient_id');

        if ($patientId) {
            $patient = Patient::find($patientId);
            if ($patient) {
                return "Nuevo Seguimiento para {$patient->full_name}";
            }
        }

        return 'Nuevo Seguimiento Mensual';
    }

    // public function getSubheading(): ?string
    // {
    //     $sourceType = request()->query('source_type');
    //     $sourceId = request()->query('source_id');

    //     if ($sourceType === 'mental_disorder' && $sourceId) {
    //         $mentalDisorder = MentalDisorder::with('patient')->find($sourceId);
    //         if ($mentalDisorder) {
    //             return "Trastorno Mental: {$mentalDisorder->diagnosis_description} | Documento: {$mentalDisorder->patient->document_number}";
    //         }
    //     }

    //     return 'Complete la información del seguimiento mensual';
    // }

    public function getSubheading(): ?string
    {
        $sourceType = request()->query('source_type');
        $sourceId = request()->query('source_id');

        if ($sourceType && $sourceId) {
            return match ($sourceType) {
                'mental_disorder' => optional(MentalDisorder::with('patient')->find($sourceId), function ($disorder) {
                    return "Trastorno Mental: {$disorder->diagnosis_description} | Documento: {$disorder->patient->document_number}";
                }),
                'suicide_attempt' => optional(SuicideAttempt::with('patient')->find($sourceId), function ($attempt) {
                    return "Intento de Suicidio N° {$attempt->attempt_number} | Documento: {$attempt->patient->document_number}";
                }),
                'substance_consumption' => optional(SubstanceConsumption::with('patient')->find($sourceId), function ($consumption) {
                    return "Consumo SPA: {$consumption->diagnosis} | Documento: {$consumption->patient->document_number}";
                }),
                default => null,
            };
        }

        return 'Complete la información del seguimiento mensual';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $sourceType = request()->query('source_type');
        $sourceId = request()->query('source_id');

        if ($sourceType && $sourceId) {
            switch ($sourceType) {
                case 'mental_disorder':
                    $data['followupable_type'] = MentalDisorder::class;
                    $data['followupable_id'] = $sourceId;
                    break;

                case 'suicide_attempt':
                    $data['followupable_type'] = SuicideAttempt::class;
                    $data['followupable_id'] = $sourceId;
                    break;

                case 'substance_consumption':
                    $data['followupable_type'] = SubstanceConsumption::class;
                    $data['followupable_id'] = $sourceId;
                    break;

                default:
                    $data['followupable_type'] = Patient::class;
                    $data['followupable_id'] = request()->query('patient_id');
            }
        } else {
            // Fallback al paciente directo si no hay source
            $data['followupable_type'] = Patient::class;
            $data['followupable_id'] = $data['patient_id'] ?? request()->query('patient_id');
        }

        return $data;
    }

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     // Asegurar que followupable_type esté configurado
    //     $data['followupable_type'] = Patient::class;

    //     // Si viene desde mental disorder, podemos guardar referencia adicional
    //     $sourceType = request()->query('source_type');
    //     $sourceId = request()->query('source_id');

    //     if ($sourceType && $sourceId) {
    //         // Agregar información de referencia si se implementó el campo source_reference
    //         if (array_key_exists('source_reference', $data)) {
    //             $data['source_reference'] = [
    //                 'type' => $sourceType,
    //                 'id' => $sourceId
    //             ];
    //         }
    //     }

    //     return $data;
    // }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Verificar si ya existe un seguimiento para este paciente en este mes
        $existingFollowup = MonthlyFollowup::where('followupable_id', $data['followupable_id'])
            ->where('followupable_type', $data['followupable_type'])
            ->where('year', $data['year'])
            ->where('month', $data['month'])
            ->first();

        if ($existingFollowup) {
            // Obtener información del paciente para el mensaje
            $patient = Patient::find($data['followupable_id']);
            $patientName = $patient ? $patient->full_name : 'el paciente';
            $monthName = $this->getMonthName($data['month']);

            // Mostrar notificación de que ya existe
            Notification::make()
                ->title('Seguimiento ya existe')
                ->body("Ya existe un seguimiento para {$patientName} en {$monthName} {$data['year']}. Redirigiendo a editarlo.")
                ->warning()
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('ver_existente')
                        ->button()
                        ->url($this->getResource()::getUrl('edit', ['record' => $existingFollowup]))
                        ->label('Editar seguimiento existente'),
                    \Filament\Notifications\Actions\Action::make('crear_nuevo')
                        ->button()
                        ->action('createAnyway')
                        ->label('Crear nuevo seguimiento')
                        ->color('danger'),
                ])
                ->send();

            // Redirigir a la edición del seguimiento existente
            $this->redirect($this->getResource()::getUrl('edit', ['record' => $existingFollowup]));

            return $existingFollowup;
        }

        // Si no existe, crear normalmente
        try {
            return static::getModel()::create($data);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Manejo adicional por si la verificación anterior falló
            Notification::make()
                ->title('Error: Seguimiento duplicado')
                ->body('Ya existe un seguimiento para este paciente en este mes. Intente editarlo en su lugar.')
                ->danger()
                ->send();

            // Intentar encontrar el seguimiento existente
            $existingFollowup = MonthlyFollowup::where('followupable_id', $data['followupable_id'])
                ->where('followupable_type', $data['followupable_type'])
                ->where('year', $data['year'])
                ->where('month', $data['month'])
                ->first();

            if ($existingFollowup) {
                $this->redirect($this->getResource()::getUrl('edit', ['record' => $existingFollowup]));
                return $existingFollowup;
            }

            throw $e; // Re-lanzar si no podemos manejar el error
        }
    }

    public function createAnyway(): void
    {
        // Método para forzar la creación eliminando temporalmente la restricción
        // o modificando los datos para evitar el conflicto

        $data = $this->form->getState();

        // Opción 1: Cambiar ligeramente la fecha para evitar conflicto
        $data['followup_date'] = now()->addMinutes(1);

        // Opción 2: Agregar un identificador único en la descripción
        $data['description'] = ($data['description'] ?? '') . "\n\n[Seguimiento adicional - " . now()->format('H:i:s') . "]";

        try {
            $record = static::getModel()::create($data);

            Notification::make()
                ->title('Seguimiento adicional creado')
                ->body('Se ha creado un seguimiento adicional para este paciente.')
                ->success()
                ->send();

            $this->redirect($this->getRedirectUrl());
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al crear seguimiento')
                ->body('No se pudo crear el seguimiento adicional: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function getMonthName(int $month): string
    {
        $months = [
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
            12 => 'Diciembre'
        ];

        return $months[$month] ?? (string) $month;
    }

    protected function getRedirectUrl(): string
    {
        // Redirigir de vuelta a la lista de seguimientos
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Seguimiento mensual registrado correctamente';
    }
}
