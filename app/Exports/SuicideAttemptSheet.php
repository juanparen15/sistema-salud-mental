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

class SuicideAttemptSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
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
            $query->where('suicide_attempt', true);
            if ($this->month && $this->year) {
                $query->whereMonth('follow_date', $this->month)
                      ->whereYear('follow_date', $this->year);
            }
        });

        return $query->get()->flatMap(function ($patient) {
            return $patient->monthlyFollowups->where('suicide_attempt', true)->map(function ($followup) use ($patient) {
                return [
                    'identification_number' => $patient->identification_number,
                    'first_name' => $patient->first_name,
                    'last_name' => $patient->last_name,
                    'age' => $patient->age,
                    'gender' => $patient->gender,
                    'follow_date' => $followup->follow_date ? Carbon::parse($followup->follow_date)->format('Y-m-d') : '',
                    'suicide_attempt_date' => $followup->suicide_attempt_date ? Carbon::parse($followup->suicide_attempt_date)->format('Y-m-d') : '',
                    'suicide_method' => $followup->suicide_method,
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
            'Fecha del Intento',
            'Método Utilizado',
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
            return "Intentos Suicidio {$this->month}-{$this->year}";
        }
        return 'Intentos de Suicidio';
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