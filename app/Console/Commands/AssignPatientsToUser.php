<?php

// ================================
// COMANDOS ARTISAN PERSONALIZADOS
// ================================

// app/Console/Commands/AssignPatientsToUser.php
namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Console\Command;

class AssignPatientsToUser extends Command
{
    protected $signature = 'mental-health:assign-patients 
                            {user_email : Email del usuario}
                            {--document_prefix= : Prefijo de documento para filtrar pacientes}
                            {--all : Asignar todos los pacientes sin asignar}';
    
    protected $description = 'Asigna pacientes a un usuario específico';

    public function handle()
    {
        $userEmail = $this->argument('user_email');
        $user = User::where('email', $userEmail)->first();

        if (!$user) {
            $this->error("Usuario con email {$userEmail} no encontrado.");
            return 1;
        }

        $query = Patient::whereNull('assigned_to');

        if ($this->option('document_prefix')) {
            $prefix = $this->option('document_prefix');
            $query->where('document_number', 'LIKE', $prefix . '%');
        }

        if ($this->option('all')) {
            $patients = $query->get();
        } else {
            $this->info('Pacientes disponibles para asignar:');
            $patients = $query->take(20)->get();
            
            if ($patients->isEmpty()) {
                $this->info('No hay pacientes sin asignar.');
                return 0;
            }

            $patients->each(function ($patient, $index) {
                $this->line("{$index}: {$patient->full_name} - {$patient->document_number}");
            });

            $selection = $this->ask('Selecciona los números de pacientes (separados por coma) o "all" para todos');
            
            if ($selection === 'all') {
                $patients = $query->get();
            } else {
                $indices = explode(',', $selection);
                $patients = $patients->whereIn(array_map('trim', $indices))->values();
            }
        }

        if ($patients->isEmpty()) {
            $this->info('No se seleccionaron pacientes.');
            return 0;
        }

        $count = 0;
        foreach ($patients as $patient) {
            $patient->update(['assigned_to' => $user->id]);
            $count++;
        }

        $this->info("✅ {$count} pacientes asignados a {$user->name} ({$user->email})");
        return 0;
    }
}