<?php

namespace App\Exports;

use App\Models\Patient;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class SubstanceConsumptionSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected $month;
    protected $year;

    public function __construct($month = null, $year = null)
    {
        $this->month = $month;
        $this->year = $year;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Patient::with(['monthlyFollowups' => function ($query) {
            if ($this->month && $this->year) {
                $query->whereMonth('follow_date', $this->month)
                      ->whereYear('follow_date', $this->year);
            }
        }])->whereHas('monthlyFollowups', function ($query) {
            $query->where('substance_use', true);
            if ($this->month && $this->year) {
                $query->whereMonth('follow_date', $this->month)
                      ->whereYear('follow_date', $this->year);
            }
        });

        return $query->get()->flatMap(function ($patient) {
            return $patient->monthlyFollowups->where('substance_use', true)->map(function ($followup) use ($patient) {
                return [
                    'identification_number' => $patient->identification_number,
                    'first_name' => $patient->first_name,
                    'last_name' => $patient->last_name,
                    'age' => $patient->age,
                    'gender' => $patient->gender,
                    'follow_date' => $followup->follow_date ? Carbon::parse($followup->follow_date)->format('Y-m-d') : '',
                    'substance_type' => $followup->substance_type,
                    'consumption_frequency' => $followup->consumption_frequency,
                    'consumption_duration' => $followup->consumption_duration,
                    'impact_level' => $followup->impact_level,
                    'intervention_provided' => $followup->intervention_provided ? 'Sí' : 'No',
                    'referral_made' => $followup->referral_made ? 'Sí' : 'No',
                    'referral_institution' => $followup->referral_institution,
                    'observations' => $followup->observations,
                    'created_by' => $followup->user ? $followup->user->name : '',
                ];
            });
        });
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Número de Identificación',
            'Nombres',
            'Apellidos',
            'Edad',
            'Género',
            'Fecha de Seguimiento',
            'Tipo de Sustancia',
            'Frecuencia de Consumo',
            'Duración del Consumo',
            'Nivel de Impacto',
            'Intervención Realizada',
            'Remisión Realizada',
            'Institución de Remisión',
            'Observaciones',
            'Registrado por',
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        if ($this->month && $this->year) {
            return "Consumo Sustancias {$this->month}-{$this->year}";
        }
        return 'Consumo de Sustancias';
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}