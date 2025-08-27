<?php

namespace App\Http\Resources;

use App\Filament\Resources\MonthlyFollowupResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
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
            'identification_number' => $this->identification_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->first_name . ' ' . $this->last_name,
            'age' => $this->age,
            'gender' => $this->gender,
            'gender_label' => $this->getGenderLabel(),
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'municipality' => $this->municipality,
            'emergency_contact' => $this->emergency_contact,
            'emergency_phone' => $this->emergency_phone,
            'monthly_followups_count' => $this->monthlyFollowups->count(),
            'latest_followup' => $this->getLatestFollowup(),
            'risk_indicators' => $this->getRiskIndicators(),
            'monthly_followups' => MonthlyFollowupResource::collection($this->whenLoaded('monthlyFollowups')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get gender label
     */
    private function getGenderLabel(): string
    {
        return match($this->gender) {
            'M' => 'Masculino',
            'F' => 'Femenino',
            'Otro' => 'Otro',
            default => $this->gender,
        };
    }

    /**
     * Get latest followup information
     */
    private function getLatestFollowup(): ?array
    {
        $latestFollowup = $this->monthlyFollowups
            ->sortByDesc('follow_date')
            ->first();

        if (!$latestFollowup) {
            return null;
        }

        return [
            'id' => $latestFollowup->id,
            'follow_date' => $latestFollowup->follow_date?->format('Y-m-d'),
            'mood_state' => $latestFollowup->mood_state,
            'days_ago' => $latestFollowup->follow_date?->diffInDays(now()),
            'risk_indicators' => [
                'suicide_risk' => $latestFollowup->suicide_risk,
                'suicide_attempt' => $latestFollowup->suicide_attempt,
                'substance_use' => $latestFollowup->substance_use,
                'violence_risk' => $latestFollowup->violence_risk,
            ],
        ];
    }

    /**
     * Get risk indicators from all followups
     */
    private function getRiskIndicators(): array
    {
        $followups = $this->monthlyFollowups;
        
        return [
            'has_suicide_risk' => $followups->where('suicide_risk', true)->isNotEmpty(),
            'has_suicide_attempts' => $followups->where('suicide_attempt', true)->isNotEmpty(),
            'has_substance_use' => $followups->where('substance_use', true)->isNotEmpty(),
            'has_violence_risk' => $followups->where('violence_risk', true)->isNotEmpty(),
            'total_suicide_attempts' => $followups->where('suicide_attempt', true)->count(),
            'recent_risks' => $followups
                ->where('follow_date', '>=', now()->subDays(30))
                ->filter(function ($followup) {
                    return $followup->suicide_risk || 
                           $followup->suicide_attempt || 
                           $followup->substance_use || 
                           $followup->violence_risk;
                })
                ->isNotEmpty(),
        ];
    }
}