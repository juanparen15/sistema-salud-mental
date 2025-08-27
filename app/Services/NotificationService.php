<?php

namespace App\Services;

use App\Models\User;
use App\Models\Patient;
use App\Models\MonthlyFollowup;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Enviar notificación de seguimiento crítico
     */
    public function notifyCriticalFollowup(MonthlyFollowup $followup)
    {
        try {
            $patient = $followup->followupable;

            if (!$patient instanceof Patient) {
                return;
            }

            $alertLevel = $this->calculateAlertLevel($followup);

            if ($alertLevel === 'high') {
                $this->sendAlert($patient, $followup);
            }
        } catch (\Exception $e) {
            Log::error('Error enviando notificación crítica: ' . $e->getMessage());
        }
    }

    /**
     * Calcular nivel de alerta basado en el seguimiento
     */
    private function calculateAlertLevel(MonthlyFollowup $followup): string
    {
        $score = 0;

        // Status que requieren atención inmediata
        if (in_array($followup->status, ['not_contacted', 'refused'])) {
            $score += 10;
        }

        // Seguimientos pendientes antiguos
        if ($followup->status === 'pending' && $followup->followup_date < now()->subDays(7)) {
            $score += 8;
        }

        // Palabras clave críticas en descripción
        $criticalKeywords = ['crisis', 'emergencia', 'suicidio', 'violencia', 'urgente'];
        $description = strtolower($followup->description ?? '');

        foreach ($criticalKeywords as $keyword) {
            if (strpos($description, $keyword) !== false) {
                $score += 15;
                break; // Solo contar una vez
            }
        }

        // Acciones críticas realizadas
        if ($followup->actions_taken) {
            $actions = json_decode($followup->actions_taken, true) ?? [];
            $criticalActions = ['Intervención en crisis', 'Remisión urgente', 'Plan de seguridad'];

            foreach ($actions as $action) {
                foreach ($criticalActions as $criticalAction) {
                    if (stripos($action, $criticalAction) !== false) {
                        $score += 12;
                        break 2; // Salir de ambos loops
                    }
                }
            }
        }

        // Determinar nivel
        if ($score >= 15) return 'high';
        if ($score >= 8) return 'medium';

        return 'low';
    }

    /**
     * Enviar alerta a usuarios apropiados
     */
    private function sendAlert(Patient $patient, MonthlyFollowup $followup)
    {
        // Obtener usuarios que deben ser notificados
        $usersToNotify = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'coordinator', 'psychologist']);
        })->get();

        $alertData = [
            'patient_name' => $patient->full_name,
            'patient_document' => $patient->document_number,
            'followup_date' => $followup->followup_date,
            'status' => $followup->status,
            'description' => $followup->description,
            'performed_by' => $followup->performedBy->name ?? 'Sistema',
            'alert_reason' => $this->getAlertReason($followup),
        ];

        foreach ($usersToNotify as $user) {
            try {
                // Log de la notificación
                Log::info("Alerta de seguimiento enviada", [
                    'recipient' => $user->email,
                    'patient_document' => $patient->document_number,
                    'followup_id' => $followup->id
                ]);

                // Aquí podrías enviar email, SMS, etc.
                // Mail::to($user->email)->send(new FollowupAlert($alertData));

            } catch (\Exception $e) {
                Log::warning("No se pudo notificar a {$user->email}: " . $e->getMessage());
            }
        }
    }

    /**
     * Obtener razón de la alerta
     */
    private function getAlertReason(MonthlyFollowup $followup): string
    {
        if ($followup->status === 'not_contacted') {
            return 'Paciente no pudo ser contactado';
        }

        if ($followup->status === 'refused') {
            return 'Paciente rechazó el seguimiento';
        }

        if ($followup->status === 'pending' && $followup->followup_date < now()->subDays(7)) {
            return 'Seguimiento pendiente por más de 7 días';
        }

        $description = strtolower($followup->description ?? '');
        $criticalKeywords = [
            'crisis' => 'Situación de crisis detectada',
            'emergencia' => 'Situación de emergencia reportada',
            'suicidio' => 'Riesgo suicida identificado',
            'violencia' => 'Riesgo de violencia detectado',
            'urgente' => 'Situación marcada como urgente'
        ];

        foreach ($criticalKeywords as $keyword => $reason) {
            if (strpos($description, $keyword) !== false) {
                return $reason;
            }
        }

        return 'Seguimiento requiere atención especial';
    }

    /**
     * Verificar seguimientos vencidos
     */
    public function checkOverdueFollowups()
    {
        $maxDays = 45; // Días sin seguimiento
        $cutoffDate = now()->subDays($maxDays);

        // Encontrar pacientes sin seguimientos recientes
        $patientsWithoutRecentFollowup = Patient::whereDoesntHave('monthlyFollowups', function ($query) use ($cutoffDate) {
            $query->where('followup_date', '>=', $cutoffDate);
        })->where('status', 'active')->get();

        $overdue = [];
        foreach ($patientsWithoutRecentFollowup as $patient) {
            $lastFollowup = MonthlyFollowup::where('followupable_id', $patient->id)
                ->where('followupable_type', Patient::class)
                ->latest('followup_date')
                ->first();

            $daysSince = $lastFollowup
                ? $lastFollowup->followup_date->diffInDays(now())
                : 999;

            if ($daysSince > $maxDays) {
                $overdue[] = [
                    'patient' => $patient,
                    'days_since_last' => $daysSince,
                    'last_followup_date' => $lastFollowup?->followup_date
                ];
            }
        }

        // Notificar sobre seguimientos vencidos
        if (!empty($overdue)) {
            $this->notifyOverdueFollowups($overdue);
        }

        return count($overdue);
    }

    /**
     * Notificar seguimientos vencidos
     */
    private function notifyOverdueFollowups(array $overdueList)
    {
        $coordinators = User::role('coordinator')->get();

        foreach ($coordinators as $coordinator) {
            Log::info("Notificando seguimientos vencidos", [
                'recipient' => $coordinator->email,
                'overdue_count' => count($overdueList)
            ]);

            // Aquí enviarías el email con la lista de pacientes sin seguimiento
            // Mail::to($coordinator->email)->send(new OverdueFollowupsReport($overdueList));
        }
    }

    /**
     * Generar resumen mensual
     */
    public function generateMonthlySummary($month = null, $year = null)
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        $summary = [
            'period' => "{$month}/{$year}",
            'total_followups' => MonthlyFollowup::where('month', $month)
                ->where('year', $year)->count(),
            'completed' => MonthlyFollowup::where('month', $month)
                ->where('year', $year)
                ->where('status', 'completed')->count(),
            'pending' => MonthlyFollowup::where('month', $month)
                ->where('year', $year)
                ->where('status', 'pending')->count(),
            'not_contacted' => MonthlyFollowup::where('month', $month)
                ->where('year', $year)
                ->where('status', 'not_contacted')->count(),
            'refused' => MonthlyFollowup::where('month', $month)
                ->where('year', $year)
                ->where('status', 'refused')->count(),
            'new_patients' => Patient::whereMonth('created_at', $month)
                ->whereYear('created_at', $year)->count(),
        ];

        Log::info("Resumen mensual generado", $summary);

        return $summary;
    }

    /**
     * Programar notificaciones automáticas
     */
    public function scheduleAutomaticNotifications()
    {
        Log::info('Ejecutando notificaciones automáticas programadas');

        $overdueCount = $this->checkOverdueFollowups();
        $summary = $this->generateMonthlySummary();

        Log::info("Notificaciones procesadas", [
            'overdue_followups' => $overdueCount,
            'monthly_summary' => $summary
        ]);
    }
}
