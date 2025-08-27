<?php

namespace App\Jobs;

use App\Models\Patient;
use App\Models\SuicideAttempt;
use App\Models\SubstanceConsumption;
use App\Models\User;
use App\Notifications\HighRiskAlertNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class DetectHighRiskPatients implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function handle(): void
    {
        $this->detectMultipleSuicideAttempts();
        $this->detectRecentSuicideAttempts();
        $this->detectHighRiskConsumption();
        $this->detectTreatmentAbandonment();
    }
    
    protected function detectMultipleSuicideAttempts(): void
    {
        $patients = Patient::whereHas('suicideAttempts', function($query) {
            $query->where('status', 'active')
                  ->where('attempt_number', '>=', 2);
        })->get();
        
        foreach ($patients as $patient) {
            $latestAttempt = $patient->suicideAttempts()
                ->where('status', 'active')
                ->latest('event_date')
                ->first();
            
            if ($latestAttempt) {
                $this->notifyHighRisk($patient, 'multiple_attempts', [
                    'número_intentos' => $latestAttempt->attempt_number,
                    'fecha_último' => $latestAttempt->event_date->format('d/m/Y'),
                ]);
            }
        }
    }
    
    protected function detectRecentSuicideAttempts(): void
    {
        $recentAttempts = SuicideAttempt::where('status', 'active')
            ->where('event_date', '>=', Carbon::now()->subDays(7))
            ->with('patient')
            ->get();
        
        foreach ($recentAttempts as $attempt) {
            $this->notifyHighRisk($attempt->patient, 'recent_attempt', [
                'fecha_evento' => $attempt->event_date->format('d/m/Y'),
                'mecanismo' => $attempt->mechanism,
                'factor_desencadenante' => $attempt->trigger_factor,
            ]);
        }
    }
    
    protected function detectHighRiskConsumption(): void
    {
        $highRiskConsumptions = SubstanceConsumption::where('status', 'active')
            ->where('consumption_level', 'Alto Riesgo')
            ->with('patient')
            ->get();
        
        foreach ($highRiskConsumptions as $consumption) {
            // Verificar si no tiene seguimientos recientes
            $lastFollowup = $consumption->followups()
                ->where('status', 'completed')
                ->latest('followup_date')
                ->first();
            
            if (!$lastFollowup || $lastFollowup->followup_date < Carbon::now()->subDays(30)) {
                $this->notifyHighRisk($consumption->patient, 'high_consumption', [
                    'sustancias' => implode(', ', $consumption->substances_used),
                    'nivel_consumo' => $consumption->consumption_level,
                    'días_sin_seguimiento' => $lastFollowup 
                        ? Carbon::now()->diffInDays($lastFollowup->followup_date)
                        : 'Sin seguimientos previos',
                ]);
            }
        }
    }
    
    protected function detectTreatmentAbandonment(): void
    {
        // Detectar pacientes sin seguimientos en los últimos 60 días
        $patients = Patient::whereHas('mentalDisorders', function($query) {
            $query->where('status', 'active');
        })
        ->orWhereHas('suicideAttempts', function($query) {
            $query->where('status', 'active');
        })
        ->orWhereHas('substanceConsumptions', function($query) {
            $query->whereIn('status', ['active', 'in_treatment']);
        })
        ->get();
        
        foreach ($patients as $patient) {
            $hasRecentFollowup = false;
            
            // Verificar seguimientos en todos los tipos de casos
            foreach (['mentalDisorders', 'suicideAttempts', 'substanceConsumptions'] as $relation) {
                foreach ($patient->$relation as $case) {
                    if ($case->followups()->where('followup_date', '>=', Carbon::now()->subDays(60))->exists()) {
                        $hasRecentFollowup = true;
                        break 2;
                    }
                }
            }
            
            if (!$hasRecentFollowup) {
                $this->notifyHighRisk($patient, 'treatment_abandonment', [
                    'días_sin_contacto' => '60+',
                    'requiere' => 'Contacto urgente',
                ]);
            }
        }
    }
    
    protected function notifyHighRisk(Patient $patient, string $riskType, array $details): void
    {
        // Notificar a todos los coordinadores y administradores
        $users = User::role(['coordinator', 'admin'])->get();
        
        foreach ($users as $user) {
            $user->notify(new HighRiskAlertNotification($patient, $riskType, $details));
        }
    }
}