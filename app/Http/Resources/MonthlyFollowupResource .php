<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonthlyFollowupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'patient' => [
                'id' => $this->patient->id,
                'identification_number' => $this->patient->identification_number,
                'full_name' => $this->patient->first_name . ' ' . $this->patient->last_name,
            ],
            'follow_date' => $this->follow_date?->format('Y-m-d'),
            'follow_date_formatted' => $this->follow_date?->format('d/m/Y'),
            'days_ago' => $this->follow_date?->diffInDays(now()),
            'mood_state' => $this->mood_state,
            'mood_state_color' => $this->getMoodStateColor(),
            
            // Risk factors
            'risk_factors' => [
                'suicide_risk' => $this->suicide_risk,
                'suicide_attempt' => $this->suicide_attempt,
                'substance_use' => $this->substance_use,
                'violence_risk' => $this->violence_risk,
            ],
            
            // Suicide attempt details
            'suicide_details' => $this->when($this->suicide_attempt, [
                'attempt_date' => $this->suicide_attempt_date?->format('Y-m-d'),
                'method' => $this->suicide_method,
            ]),
            
            // Substance use details
            'substance_details' => $this->when($this->substance_use, [
                'type' => $this->substance_type,
                'frequency' => $this->consumption_frequency,
                'duration' => $this->consumption_duration,
                'impact_level' => $this->impact_level,
            ]),
            
            // Intervention details
            'intervention' => [
                'provided' => $this->intervention_provided,
                'referral_made' => $this->referral_made,
                'referral_institution' => $this->referral_institution,
                'next_appointment' => $this->next_appointment?->format('Y-m-d'),
            ],
            
            'observations' => $this->observations,
            'created_by' => $this->whenLoaded('user', [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Risk summary
            'risk_summary' => $this->getRiskSummary(),
        ];
    }

    /**
     * Get mood state color for UI
     */
    private function getMoodStateColor(): string
    {
        return match($this->mood_state) {
            'Muy Bueno' => 'success',
            'Bueno' => 'primary',
            'Regular' => 'warning',
            'Malo' => 'danger',
            'Muy Malo' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get risk summary
     */
    private function getRiskSummary(): array
    {
        $risks = [];
        $riskLevel = 'low';
        
        if ($this->suicide_attempt) {
            $risks[] = 'Intento de Suicidio';
            $riskLevel = 'critical';
        }
        
        if ($this->suicide_risk) {
            $risks[] = 'Riesgo de Suicidio';
            $riskLevel = $riskLevel === 'low' ? 'high' : $riskLevel;
        }
        
        if ($this->violence_risk) {
            $risks[] = 'Riesgo de Violencia';
            $riskLevel = $riskLevel === 'low' ? 'high' : $riskLevel;
        }
        
        if ($this->substance_use) {
            $risks[] = 'Consumo de Sustancias';
            $riskLevel = $riskLevel === 'low' ? 'medium' : $riskLevel;
        }
        
        return [
            'level' => $riskLevel,
            'level_label' => $this->getRiskLevelLabel($riskLevel),
            'indicators' => $risks,
            'indicators_count' => count($risks),
            'color' => $this->getRiskLevelColor($riskLevel),
        ];
    }

    /**
     * Get risk level label
     */
    private function getRiskLevelLabel(string $level): string
    {
        return match($level) {
            'critical' => 'Crítico',
            'high' => 'Alto',
            'medium' => 'Medio',
            'low' => 'Bajo',
            default => 'Sin Determinar',
        };
    }

    /**
     * Get risk level color
     */
    private function getRiskLevelColor(string $level): string
    {
        return match($level) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray',
        };
    }
}