<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\MentalDisorder;
use App\Models\SuicideAttempt;
use App\Models\SubstanceConsumption;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MentalHealthExport;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ExportService
{
    public function exportMonthlyReport(int $year, int $month): string
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $data = [
            'mental_disorders' => $this->getMentalDisordersData($startDate, $endDate),
            'suicide_attempts' => $this->getSuicideAttemptsData($startDate, $endDate),
            'substance_consumptions' => $this->getSubstanceConsumptionsData($startDate, $endDate),
        ];

        $fileName = "reporte_salud_mental_{$year}_{$month}.xlsx";

        Excel::store(new MentalHealthExport($data), $fileName, 'public');

        return $fileName;
    }

    protected function getMentalDisordersData($startDate, $endDate): Collection
    {
        return MentalDisorder::with(['patient', 'followups' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('followup_date', [$startDate, $endDate]);
        }])
            ->whereBetween('admission_date', [$startDate, $endDate])
            ->orWhereHas('followups', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('followup_date', [$startDate, $endDate]);
            })
            ->get()
            ->map(function ($disorder) {
                $patient = $disorder->patient;
                return [
                    'fecha_ingreso' => $disorder->admission_date->format('d/m/Y'),
                    'tipo_ingreso' => $disorder->admission_type,
                    'ingreso_por' => $disorder->admission_via,
                    'documento' => $patient->document_number,
                    'tipo_documento' => $patient->document_type,
                    'nombre' => $patient->full_name,
                    'sexo' => $patient->gender,
                    'fecha_nacimiento' => $patient->birth_date->format('d/m/Y'),
                    'edad' => $patient->age,
                    'telefono' => $patient->phone,
                    'direccion' => $patient->address,
                    'vereda' => $patient->village,
                    'eps_codigo' => $patient->eps_code,
                    'eps_nombre' => $patient->eps_name,
                    'area_servicio' => $disorder->service_area,
                    'diagnostico_codigo' => $disorder->diagnosis_code,
                    'diagnostico' => $disorder->diagnosis_description,
                    'fecha_diagnostico' => $disorder->diagnosis_date->format('d/m/Y'),
                    'tipo_diagnostico' => $disorder->diagnosis_type,
                    'observacion' => $disorder->additional_observation,
                    'seguimientos' => $disorder->followups->pluck('description')->join('; '),
                ];
            });
    }

    protected function getSuicideAttemptsData($startDate, $endDate): Collection
    {
        return SuicideAttempt::with(['patient', 'followups'])
            ->whereBetween('event_date', [$startDate, $endDate])
            ->get()
            ->map(function ($attempt) {
                $patient = $attempt->patient;
                return [
                    'fecha_ingreso' => $attempt->event_date->format('d/m/Y'),
                    'semana' => $attempt->week_number,
                    'ingreso_por' => $attempt->admission_via,
                    'tipo_doc' => $patient->document_type,
                    'documento' => $patient->document_number,
                    'nombre' => $patient->full_name,
                    'fecha_nacimiento' => $patient->birth_date->format('d/m/Y'),
                    'edad' => $patient->age,
                    'sexo' => $patient->gender,
                    'telefono' => $patient->phone,
                    'direccion' => $patient->address,
                    'barrio' => $patient->neighborhood,
                    'vereda' => $patient->village,
                    'num_intentos' => $attempt->attempt_number,
                    'plan_beneficios' => $attempt->benefit_plan,
                    'desencadenante' => $attempt->trigger_factor,
                    'factores_riesgo' => implode(', ', $attempt->risk_factors ?? []),
                    'mecanismo' => $attempt->mechanism,
                    'observacion' => $attempt->additional_observation,
                    'seguimientos' => $attempt->followups->pluck('description')->join('; '),
                ];
            });
    }

    protected function getSubstanceConsumptionsData($startDate, $endDate): Collection
    {
        return SubstanceConsumption::with(['patient', 'followups'])
            ->whereBetween('admission_date', [$startDate, $endDate])
            ->get()
            ->map(function ($consumption) {
                $patient = $consumption->patient;
                return [
                    'fecha_ingreso' => $consumption->admission_date->format('d/m/Y'),
                    'ingreso_por' => $consumption->admission_via,
                    'tipo_doc' => $patient->document_type,
                    'documento' => $patient->document_number,
                    'nombre' => $patient->full_name,
                    'fecha_nacimiento' => $patient->birth_date->format('d/m/Y'),
                    'telefono' => $patient->phone,
                    'sexo' => $patient->gender,
                    'eps' => $patient->eps_name,
                    'diagnostico' => $consumption->diagnosis,
                    'sustancias' => implode(', ', $consumption->substances_used ?? []),
                    'nivel_consumo' => $consumption->consumption_level,
                    'observacion' => $consumption->additional_observation,
                    'seguimientos' => $consumption->followups->pluck('description')->join('; '),
                ];
            });
    }
}
