<?php

namespace App\Observers;

use App\Models\MonthlyFollowup;
use App\Models\Patient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MonthlyFollowupObserver
{
    /**
     * Handle the MonthlyFollowup "created" event.
     */
    public function created(MonthlyFollowup $monthlyFollowup): void
    {
        $this->processNewFollowup($monthlyFollowup);
    }

    /**
     * Handle the MonthlyFollowup "updated" event.
     */
    public function updated(MonthlyFollowup $monthlyFollowup): void
    {
        // Solo procesar si cambió algún campo importante
        if ($this->hasImportantChanges($monthlyFollowup)) {
            $this->processNewFollowup($monthlyFollowup);
        }
    }

    /**
     * Handle the MonthlyFollowup "deleted" event.
     */
    public function deleted(MonthlyFollowup $monthlyFollowup): void
    {
        $patient = $this->getPatient($monthlyFollowup);

        if ($patient) {
            Log::info("Seguimiento eliminado", [
                'patient_document' => $patient->document_number,
                'patient_name' => $patient->full_name,
                'followup_id' => $monthlyFollowup->id,
                'followup_date' => $monthlyFollowup->followup_date,
                'deleted_by' => auth()->user()->name ?? 'Sistema'
            ]);
        }
    }

    /**
     * Procesar nuevo seguimiento o cambios importantes
     */
    private function processNewFollowup(MonthlyFollowup $followup): void
    {
        try {
            $patient = $this->getPatient($followup);

            if (!$patient) {
                Log::warning('No se pudo obtener el paciente del seguimiento', [
                    'followup_id' => $followup->id
                ]);
                return;
            }

            // Verificar si necesita atención especial
            if ($this->needsSpecialAttention($followup)) {
                Log::info("Seguimiento que requiere atención especial", [
                    'patient_document' => $patient->document_number,
                    'patient_name' => $patient->full_name,
                    'status' => $followup->status,
                    'followup_date' => $followup->followup_date
                ]);

                // Actualizar estadísticas
                $this->updateFollowupStatistics();
            }

            // Verificar patrones de seguimiento
            $this->checkFollowupPatterns($followup, $patient);

            // Actualizar cache de estadísticas del paciente
            $this->updatePatientStatistics($patient);
        } catch (\Exception $e) {
            Log::error('Error procesando seguimiento en Observer: ' . $e->getMessage(), [
                'followup_id' => $followup->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Obtener el paciente desde la relación polimórfica
     */
    private function getPatient(MonthlyFollowup $followup): ?Patient
    {
        if ($followup->followupable_type === Patient::class) {
            return $followup->followupable;
        }

        return null;
    }

    /**
     * Verificar si el seguimiento necesita atención especial
     */
    private function needsSpecialAttention(MonthlyFollowup $followup): bool
    {
        // Status que requieren atención
        if (in_array($followup->status, ['not_contacted', 'refused'])) {
            return true;
        }

        // Seguimientos pendientes por mucho tiempo
        if ($followup->status === 'pending' && $followup->followup_date < now()->subDays(7)) {
            return true;
        }

        // Verificar palabras clave en la descripción que indican criticidad
        $criticalKeywords = [
            'crisis',
            'emergencia',
            'suicidio',
            'violencia',
            'drogas',
            'alcohol',
            'depresión severa',
            'psicosis',
            'agitación',
            'autolesión',
            'urgente',
            'inmediato',
            'crítico'
        ];

        $description = strtolower($followup->description ?? '');
        foreach ($criticalKeywords as $keyword) {
            if (strpos($description, $keyword) !== false) {
                return true;
            }
        }

        // Verificar acciones tomadas que indican criticidad
        if ($followup->actions_taken) {
            $actions = json_decode($followup->actions_taken, true) ?? [];
            $criticalActions = [
                'Intervención en crisis',
                'Remisión urgente',
                'Hospitalización',
                'Plan de seguridad',
                'Contacto con familia',
                'Seguimiento intensivo'
            ];

            foreach ($actions as $action) {
                foreach ($criticalActions as $criticalAction) {
                    if (stripos($action, $criticalAction) !== false) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Verificar si hubo cambios importantes
     */
    private function hasImportantChanges(MonthlyFollowup $followup): bool
    {
        $importantFields = [
            'status',
            'description',
            'actions_taken',
            'next_followup'
        ];

        foreach ($importantFields as $field) {
            if ($followup->isDirty($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar patrones de seguimiento en el tiempo
     */
    private function checkFollowupPatterns(MonthlyFollowup $followup, Patient $patient): void
    {
        // Obtener seguimientos recientes del paciente
        $recentFollowups = MonthlyFollowup::where('followupable_id', $patient->id)
            ->where('followupable_type', Patient::class)
            ->where('id', '!=', $followup->id)
            ->where('followup_date', '>=', now()->subMonths(6))
            ->orderBy('followup_date', 'desc')
            ->take(6)
            ->get();

        // Patrón de seguimientos fallidos
        if ($this->detectFailedFollowupPattern($recentFollowups)) {
            Log::warning("Patrón de seguimientos fallidos detectado", [
                'patient_document' => $patient->document_number,
                'patient_name' => $patient->full_name,
                'pattern_type' => 'seguimientos_fallidos'
            ]);
        }

        // Patrón de seguimientos irregulares
        if ($this->detectIrregularPattern($recentFollowups)) {
            Log::info("Patrón de seguimientos irregulares detectado", [
                'patient_document' => $patient->document_number,
                'patient_name' => $patient->full_name,
                'pattern_type' => 'seguimientos_irregulares'
            ]);
        }
    }

    /**
     * Detectar patrón de seguimientos fallidos
     */
    private function detectFailedFollowupPattern($followups): bool
    {
        if ($followups->count() < 3) return false;

        $failedCount = 0;
        foreach ($followups as $followup) {
            if (in_array($followup->status, ['not_contacted', 'refused'])) {
                $failedCount++;
            }
        }

        // Si más del 60% de seguimientos fallan
        return ($failedCount / $followups->count()) > 0.6;
    }

    /**
     * Detectar patrón irregular de seguimientos
     */
    private function detectIrregularPattern($followups): bool
    {
        if ($followups->count() < 4) return false;

        $pendingCount = 0;
        foreach ($followups as $followup) {
            if ($followup->status === 'pending') {
                $pendingCount++;
            }
        }

        // Si más del 50% de seguimientos están pendientes
        return ($pendingCount / $followups->count()) > 0.5;
    }

    /**
     * Actualizar estadísticas de seguimientos
     */
    private function updateFollowupStatistics(): void
    {
        $today = now()->format('Y-m-d');
        $cacheKey = "followup_stats_{$today}";

        Cache::forget($cacheKey);
        Cache::remember($cacheKey, now()->addDay(), function () {
            return [
                'completed' => MonthlyFollowup::whereDate('created_at', today())
                    ->where('status', 'completed')->count(),
                'pending' => MonthlyFollowup::whereDate('created_at', today())
                    ->where('status', 'pending')->count(),
                'not_contacted' => MonthlyFollowup::whereDate('created_at', today())
                    ->where('status', 'not_contacted')->count(),
                'refused' => MonthlyFollowup::whereDate('created_at', today())
                    ->where('status', 'refused')->count(),
            ];
        });
    }

    /**
     * Actualizar estadísticas del paciente en cache
     */
    private function updatePatientStatistics(Patient $patient): void
    {
        $cacheKey = "patient_stats_{$patient->id}";

        Cache::forget($cacheKey);
        Cache::remember($cacheKey, now()->addHours(24), function () use ($patient) {
            $followups = MonthlyFollowup::where('followupable_id', $patient->id)
                ->where('followupable_type', Patient::class);

            return [
                'total_followups' => $followups->count(),
                'completed_followups' => $followups->where('status', 'completed')->count(),
                'pending_followups' => $followups->where('status', 'pending')->count(),
                'failed_followups' => $followups->whereIn('status', ['not_contacted', 'refused'])->count(),
                'last_followup' => $followups->latest('followup_date')->first()?->followup_date,
                'current_status' => $this->calculateCurrentStatus($patient),
            ];
        });
    }

    /**
     * Calcular estado actual del paciente
     */
    private function calculateCurrentStatus(Patient $patient): string
    {
        $latestFollowup = MonthlyFollowup::where('followupable_id', $patient->id)
            ->where('followupable_type', Patient::class)
            ->latest('followup_date')
            ->first();

        if (!$latestFollowup) {
            return 'without_followup';
        }

        // Verificar si el seguimiento es reciente (últimos 30 días)
        $isRecent = $latestFollowup->followup_date >= now()->subDays(30);

        switch ($latestFollowup->status) {
            case 'completed':
                return $isRecent ? 'up_to_date' : 'needs_followup';
            case 'pending':
                return 'pending_contact';
            case 'not_contacted':
                return 'contact_failed';
            case 'refused':
                return 'refused_service';
            default:
                return 'unknown';
        }
    }
}
