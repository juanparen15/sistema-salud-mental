<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MentalHealthImport;
use App\Models\Patient;
use App\Models\MentalDisorder;
use App\Models\SuicideAttempt;
use App\Models\SubstanceConsumption;

class ImportExcelDataCommand extends Command
{
    protected $signature = 'mental-health:import {file : Path to Excel file}';
    protected $description = 'Import mental health data from Excel file';

    public function handle()
    {
        $filePath = $this->argument('file');
        
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }
        
        $this->info('Starting import process...');
        
        try {
            Excel::import(new MentalHealthImport, $filePath);
            
            $this->info('Import completed successfully!');
            $this->table(
                ['Entity', 'Count'],
                [
                    ['Patients', Patient::count()],
                    ['Mental Disorders', MentalDisorder::count()],
                    ['Suicide Attempts', SuicideAttempt::count()],
                    ['Substance Consumptions', SubstanceConsumption::count()],
                ]
            );
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return 1;
        }
    }
}