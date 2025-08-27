<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\MonthlyFollowup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Faker\Factory as Faker;

class DemoDataSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('es_ES');

        // Obtener usuarios existentes para asignar seguimientos
        $users = User::all();
        if ($users->isEmpty()) {
            $this->command->warn('No hay usuarios. Ejecuta primero RolesAndPermissionsSeeder');
            return;
        }

        $neighborhoods = [
            'Centro',
            'La Candelaria',
            'Chapinero',
            'Zona Rosa',
            'Laureles',
            'El Poblado',
            'Engativá',
            'Suba',
            'Kennedy',
            'Bosa',
            'Ciudad Bolivar',
            'San Cristóbal',
            'Usme',
            'Tunjuelito',
            'Rafael Uribe'
        ];

        $villages = [
            'El Carmen',
            'La Esperanza',
            'San José',
            'Santa Ana',
            'El Rosario',
            'La Paz',
            'Buenos Aires',
            'El Progreso',
            'Las Flores',
            'San Antonio'
        ];

        $epsOptions = [
            ['code' => 'EPS001', 'name' => 'Nueva EPS'],
            ['code' => 'EPS002', 'name' => 'Sanitas EPS'],
            ['code' => 'EPS003', 'name' => 'Sura EPS'],
            ['code' => 'EPS004', 'name' => 'Famisanar EPS'],
            ['code' => 'EPS005', 'name' => 'Compensar EPS'],
            ['code' => 'EPS006', 'name' => 'Colmédica EPS'],
            ['code' => 'EPS007', 'name' => 'Cruz Blanca EPS'],
        ];

        $this->command->info('Creando pacientes de prueba...');

        // Crear 50 pacientes de prueba
        for ($i = 1; $i <= 50; $i++) {
            $birthDate = Carbon::instance($faker->dateTimeBetween('-80 years', '-15 years'));
            $eps = $faker->optional(0.8)->randomElement($epsOptions);

            $patient = Patient::create([
                'document_number' => $faker->unique()->numerify('##########'),
                'document_type' => $faker->randomElement(['CC', 'TI', 'CE', 'PA', 'RC']),
                'full_name' => $faker->firstName . ' ' . $faker->lastName . ' ' . $faker->lastName,
                'gender' => $faker->randomElement(['Masculino', 'Femenino', 'Otro']),
                'birth_date' => $birthDate->format('Y-m-d'),
                'phone' => $faker->optional(0.8)->numerify('30########'),
                'address' => $faker->optional(0.7)->streetAddress,
                'neighborhood' => $faker->optional(0.6)->randomElement($neighborhoods),
                'village' => $faker->optional(0.3)->randomElement($villages),
                'eps_code' => $eps['code'] ?? null,
                'eps_name' => $eps['name'] ?? null,
                'status' => $faker->randomElement(['active', 'inactive', 'discharged']),
                'created_at' => Carbon::instance($faker->dateTimeBetween('-1 year', 'now')),
            ]);

            // Crear seguimientos únicos por mes (respetando restricción única)
            $followupsCount = $faker->numberBetween(1, 3);
            $usedPeriods = []; // Para evitar duplicados año/mes

            for ($j = 0; $j < $followupsCount; $j++) {
                // Generar una fecha única para este paciente
                $attempts = 0;
                do {
                    $followupDate = Carbon::instance($faker->dateTimeBetween(
                        $patient->created_at,
                        'now'
                    ));
                    $year = $followupDate->year;
                    $month = $followupDate->month;
                    $periodKey = "{$year}-{$month}";
                    $attempts++;
                } while (in_array($periodKey, $usedPeriods) && $attempts < 10);

                // Si no se pudo generar fecha única después de 10 intentos, saltar
                if (in_array($periodKey, $usedPeriods)) {
                    continue;
                }

                $usedPeriods[] = $periodKey;

                // Generar acciones realistas
                $actions = [];
                $actionOptions = [
                    'Evaluación psicológica inicial',
                    'Sesión de terapia individual',
                    'Terapia familiar',
                    'Prescripción médica',
                    'Remisión a especialista',
                    'Seguimiento telefónico',
                    'Visita domiciliaria',
                    'Educación en salud mental',
                    'Intervención en crisis',
                    'Plan de seguridad'
                ];

                $numActions = $faker->numberBetween(1, 3);
                for ($k = 0; $k < $numActions; $k++) {
                    $actions[] = $faker->randomElement($actionOptions);
                }

                $followup = MonthlyFollowup::create([
                    'followupable_id' => $patient->id,
                    'followupable_type' => Patient::class,
                    'followup_date' => $followupDate->format('Y-m-d'),
                    'year' => $year,
                    'month' => $month,
                    'description' => $faker->paragraph(3),
                    'status' => $faker->randomElement(['pending', 'completed', 'not_contacted', 'refused']),
                    'next_followup' => $faker->boolean(0.7) ?
                        Carbon::instance($faker->dateTimeBetween(
                            $followupDate->format('Y-m-d'),
                            $followupDate->copy()->addMonths(2)->format('Y-m-d')
                        ))->format('Y-m-d') : null,
                    'actions_taken' => json_encode(array_unique($actions)),
                    'performed_by' => $users->random()->id,
                    'created_at' => $followupDate,
                    'updated_at' => $followupDate,
                ]);
            }

            if ($i % 10 === 0) {
                $this->command->info("Creados {$i}/50 pacientes con seguimientos...");
            }
        }

        // Crear algunos casos específicos para demostrar funcionalidades
        $this->createSpecificCases($users, $neighborhoods, $villages, $epsOptions, $faker);

        $this->command->info('✅ Datos de prueba creados exitosamente!');
        $this->showStatistics();
    }

    private function createSpecificCases($users, $neighborhoods, $villages, $epsOptions, $faker)
    {
        $this->command->info('Creando casos específicos de demostración...');

        // Caso 1: Paciente con múltiples seguimientos completados
        $activePatient = Patient::create([
            'document_number' => '99999001',
            'document_type' => 'CC',
            'full_name' => 'María Elena González Rodríguez',
            'gender' => 'Femenino',
            'birth_date' => '1995-03-15',
            'phone' => '3001234567',
            'address' => 'Carrera 15 #32-45',
            'neighborhood' => 'Centro',
            'eps_code' => 'EPS001',
            'eps_name' => 'Nueva EPS',
            'status' => 'active',
        ]);

        // Caso 1: Paciente con múltiples seguimientos completados (diferentes meses)
        $activePatient = Patient::create([
            'document_number' => '99999006',
            'document_type' => 'CC',
            'full_name' => 'María Elena González Rodríguez',
            'gender' => 'Femenino',
            'birth_date' => '1995-03-15',
            'phone' => '3001234567',
            'address' => 'Carrera 15 #32-45',
            'neighborhood' => 'Centro',
            'eps_code' => 'EPS001',
            'eps_name' => 'Nueva EPS',
            'status' => 'active',
        ]);

        // Seguimientos de los últimos 3 meses (cada uno en mes diferente)
        for ($i = 1; $i <= 3; $i++) {
            $followupDate = now()->subMonths($i);

            MonthlyFollowup::create([
                'followupable_id' => $activePatient->id,
                'followupable_type' => Patient::class,
                'followup_date' => $followupDate->format('Y-m-d'),
                'year' => $followupDate->year,
                'month' => $followupDate->month,
                'description' => "Seguimiento mensual #{$i}. Paciente muestra progreso favorable en su tratamiento. Se evidencia mejoría en el estado de ánimo y adherencia al tratamiento médico.",
                'status' => 'completed',
                'next_followup' => $followupDate->copy()->addMonth()->format('Y-m-d'),
                'actions_taken' => json_encode([
                    'Evaluación psicológica',
                    'Sesión de terapia individual',
                    'Ajuste de medicación',
                    'Plan de seguridad actualizado'
                ]),
                'performed_by' => $users->random()->id,
                'created_at' => $followupDate,
                'updated_at' => $followupDate,
            ]);
        }

        // Caso 2: Paciente con seguimiento pendiente
        $pendingPatient = Patient::create([
            'document_number' => '99999002',
            'document_type' => 'CC',
            'full_name' => 'Carlos Andrés Martínez López',
            'gender' => 'Masculino',
            'birth_date' => '1988-08-22',
            'phone' => '3009876543',
            'address' => 'Calle 45 #12-34',
            'neighborhood' => 'Laureles',
            'village' => 'El Carmen',
            'eps_code' => 'EPS003',
            'eps_name' => 'Sura EPS',
            'status' => 'active',
        ]);

        $currentDate = now();
        MonthlyFollowup::create([
            'followupable_id' => $pendingPatient->id,
            'followupable_type' => Patient::class,
            'followup_date' => $currentDate->subWeek()->format('Y-m-d'),
            'year' => $currentDate->year,
            'month' => $currentDate->month,
            'description' => 'Seguimiento programado. Paciente requiere evaluación de adherencia al tratamiento y ajuste de plan terapéutico.',
            'status' => 'pending',
            'next_followup' => now()->addWeeks(2)->format('Y-m-d'),
            'actions_taken' => json_encode([
                'Contacto telefónico inicial',
                'Programación de cita presencial'
            ]),
            'performed_by' => $users->random()->id,
        ]);

        // Caso 3: Paciente no contactado (mes anterior)
        $notContactedPatient = Patient::create([
            'document_number' => '99999003',
            'document_type' => 'TI',
            'full_name' => 'Ana Sofía Herrera Castro',
            'gender' => 'Femenino',
            'birth_date' => '2005-12-10',
            'phone' => null, // Sin teléfono para simular dificultad de contacto
            'address' => 'Rural sin dirección específica',
            'village' => 'La Esperanza',
            'eps_code' => 'EPS005',
            'eps_name' => 'Compensar EPS',
            'status' => 'active',
        ]);

        $lastMonth = now()->subMonth();
        MonthlyFollowup::create([
            'followupable_id' => $notContactedPatient->id,
            'followupable_type' => Patient::class,
            'followup_date' => $lastMonth->subDays(10)->format('Y-m-d'),
            'year' => $lastMonth->year,
            'month' => $lastMonth->month,
            'description' => 'Intento de seguimiento fallido. No fue posible contactar al paciente por los medios disponibles. Se requiere visita domiciliaria.',
            'status' => 'not_contacted',
            'next_followup' => now()->addDays(5)->format('Y-m-d'),
            'actions_taken' => json_encode([
                'Intento de contacto telefónico',
                'Búsqueda de contactos alternativos',
                'Programación de visita domiciliaria'
            ]),
            'performed_by' => $users->random()->id,
        ]);

        // Caso 4: Paciente que rechazó seguimiento (hace 2 meses)
        $refusedPatient = Patient::create([
            'document_number' => '99999004',
            'document_type' => 'CE',
            'full_name' => 'Roberto Alejandro Vargas Mendez',
            'gender' => 'Masculino',
            'birth_date' => '1975-06-18',
            'phone' => '3156789012',
            'address' => 'Avenida 68 #15-23',
            'neighborhood' => 'Kennedy',
            'status' => 'inactive',
        ]);

        $twoMonthsAgo = now()->subMonths(2);
        MonthlyFollowup::create([
            'followupable_id' => $refusedPatient->id,
            'followupable_type' => Patient::class,
            'followup_date' => $twoMonthsAgo->addDays(5)->format('Y-m-d'),
            'year' => $twoMonthsAgo->year,
            'month' => $twoMonthsAgo->month,
            'description' => 'Paciente contactado exitosamente pero rechaza participar en el seguimiento. Se brindó información sobre beneficios del programa y se respetó su decisión.',
            'status' => 'refused',
            'next_followup' => now()->addMonth()->format('Y-m-d'), // Reintento en un mes
            'actions_taken' => json_encode([
                'Contacto telefónico exitoso',
                'Información sobre programa',
                'Respeto a la decisión del paciente',
                'Programación de reintento posterior'
            ]),
            'performed_by' => $users->random()->id,
        ]);

        // Caso 5: Paciente dado de alta (hace 3 meses)
        $dischargedPatient = Patient::create([
            'document_number' => '99999005',
            'document_type' => 'CC',
            'full_name' => 'Lucia Patricia Ramírez Torres',
            'gender' => 'Femenino',
            'birth_date' => '1990-04-25',
            'phone' => '3187654321',
            'address' => 'Transversal 32 #18-56',
            'neighborhood' => 'Chapinero',
            'eps_code' => 'EPS002',
            'eps_name' => 'Sanitas EPS',
            'status' => 'discharged',
        ]);

        $threeMonthsAgo = now()->subMonths(3);
        MonthlyFollowup::create([
            'followupable_id' => $dischargedPatient->id,
            'followupable_type' => Patient::class,
            'followup_date' => $threeMonthsAgo->format('Y-m-d'),
            'year' => $threeMonthsAgo->year,
            'month' => $threeMonthsAgo->month,
            'description' => 'Seguimiento final. Paciente cumplió objetivos terapéuticos satisfactoriamente. Se realiza alta del programa con recomendaciones de mantenimiento.',
            'status' => 'completed',
            'next_followup' => null, // Sin próximo seguimiento por estar dado de alta
            'actions_taken' => json_encode([
                'Evaluación final completa',
                'Confirmación de cumplimiento de objetivos',
                'Entrega de certificado de alta',
                'Recomendaciones de mantenimiento',
                'Información de contacto para emergencias'
            ]),
            'performed_by' => $users->random()->id,
        ]);
    }

    private function showStatistics()
    {
        $totalPatients = Patient::count();
        $totalFollowups = MonthlyFollowup::count();
        $completedFollowups = MonthlyFollowup::where('status', 'completed')->count();
        $pendingFollowups = MonthlyFollowup::where('status', 'pending')->count();
        $notContactedFollowups = MonthlyFollowup::where('status', 'not_contacted')->count();
        $refusedFollowups = MonthlyFollowup::where('status', 'refused')->count();

        $activePatients = Patient::where('status', 'active')->count();
        $inactivePatients = Patient::where('status', 'inactive')->count();
        $dischargedPatients = Patient::where('status', 'discharged')->count();

        $this->command->info('📊 Estadísticas de datos creados:');
        $this->command->info("• Total de pacientes: {$totalPatients}");
        $this->command->info("  - Activos: {$activePatients}");
        $this->command->info("  - Inactivos: {$inactivePatients}");
        $this->command->info("  - Dados de alta: {$dischargedPatients}");
        $this->command->info('');
        $this->command->info("• Total de seguimientos: {$totalFollowups}");
        $this->command->info("  - Completados: {$completedFollowups}");
        $this->command->info("  - Pendientes: {$pendingFollowups}");
        $this->command->info("  - No contactados: {$notContactedFollowups}");
        $this->command->info("  - Rechazados: {$refusedFollowups}");
        $this->command->info('');
        $this->command->info('🎯 Casos específicos creados para demostración:');
        $this->command->info('• María Elena González (Doc: 1000000001) - Seguimientos completados');
        $this->command->info('• Carlos Andrés Martínez (Doc: 1000000002) - Seguimientos pendientes');
        $this->command->info('• Ana Sofía Herrera (Doc: 1000000003) - Paciente no contactado');
        $this->command->info('• Roberto Alejandro Vargas (Doc: 1000000004) - Rechazó seguimiento');
        $this->command->info('• Lucia Patricia Ramírez (Doc: 1000000005) - Paciente dado de alta');
    }
}
