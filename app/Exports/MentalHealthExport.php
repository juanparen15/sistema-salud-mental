<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;

class MentalHealthExport implements WithMultipleSheets
{
    use Exportable;
    
    protected $data;
    
    public function __construct($data)
    {
        $this->data = $data;
    }
    
    public function sheets(): array
    {
        $sheets = [];
        
        if (isset($this->data['mental_disorders'])) {
            $sheets[] = new MentalDisorderSheet($this->data['mental_disorders']);
        }
        
        if (isset($this->data['suicide_attempts'])) {
            $sheets[] = new SuicideAttemptSheet($this->data['suicide_attempts']);
        }
        
        if (isset($this->data['substance_consumptions'])) {
            $sheets[] = new SubstanceConsumptionSheet($this->data['substance_consumptions']);
        }
        
        return $sheets;
    }
}

class MentalDisorderSheet implements \Maatwebsite\Excel\Concerns\FromCollection, 
    \Maatwebsite\Excel\Concerns\WithHeadings,
    \Maatwebsite\Excel\Concerns\WithTitle,
    \Maatwebsite\Excel\Concerns\WithColumnFormatting,
    \Maatwebsite\Excel\Concerns\ShouldAutoSize
{
    protected $data;
    
    public function __construct($data)
    {
        $this->data = $data;
    }
    
    public function collection()
    {
        return $this->data;
    }
    
    public function headings(): array
    {
        return [
            'FECHA DE INGRESO',
            'TIPO DE INGRESO',
            'INGRESO POR',
            'N. DOCUMENTO',
            'TIPO DE DOCUMENTO',
            'NOMBRES Y APELLIDOS',
            'SEXO',
            'FECHA DE NACIMIENTO',
            'EDAD',
            'TELEFONO',
            'DIRECCIÓN',
            'VEREDA',
            'EPS_CODIGO',
            'EPS_NOMBRE',
            'AREA_SERVICIO',
            'DIAG_CODIGO',
            'DIAGNOSTICO',
            'FECHA_DIAGNOSTICO',
            'TIPO_DIAGNOSTICO',
            'OBSERVACIÓN',
            'SEGUIMIENTOS'
        ];
    }
    
    public function title(): string
    {
        return 'Trastornos Mentales';
    }
    
    public function columnFormats(): array
    {
        return [
            'A' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY,
            'H' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY,
            'R' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}