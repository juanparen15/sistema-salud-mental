<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Relations\HasMany;
// use Illuminate\Database\Eloquent\Builder;

// class Patient extends Model
// {
//     use HasFactory;

//     protected $fillable = [
//         'document_number',
//         'document_type',
//         'full_name',
//         'gender',
//         'birth_date',
//         'phone',
//         'address',
//         'neighborhood',
//         'village',
//         'eps_code',
//         'eps_name',
//         'status',
//     ];

//     protected $casts = [
//         'birth_date' => 'date',
//     ];

//     // protected $appends = ['age'];

//     // public function getAgeAttribute(): int
//     // {
//     //     return $this->birth_date->age;
//     // }

//     protected $appends = ['age'];

//     public function getAgeAttribute(): int
//     {
//         return $this->birth_date->age;
//     }

//     public function mentalDisorders(): HasMany
//     {
//         return $this->hasMany(MentalDisorder::class);
//     }

//     public function suicideAttempts(): HasMany
//     {
//         return $this->hasMany(SuicideAttempt::class);
//     }

//     public function substanceConsumptions(): HasMany
//     {
//         return $this->hasMany(SubstanceConsumption::class);
//     }

//     public function scopeSearch(Builder $query, ?string $search): Builder
//     {
//         return $query->when(
//             $search,
//             fn($q) => $q
//                 ->where('document_number', 'like', "%{$search}%")
//                 ->orWhere('full_name', 'like', "%{$search}%")
//         );
//     }

//     public function getActiveConditionsAttribute(): array
//     {
//         $conditions = [];

//         if ($this->mentalDisorders()->where('status', 'active')->exists()) {
//             $conditions[] = 'Trastorno Mental';
//         }

//         if ($this->suicideAttempts()->where('status', 'active')->exists()) {
//             $conditions[] = 'Intento Suicidio';
//         }

//         if ($this->substanceConsumptions()->whereIn('status', ['active', 'in_treatment'])->exists()) {
//             $conditions[] = 'Consumo SPA';
//         }

//         return $conditions;
//     }
// }

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_number',
        'document_type',
        'full_name',
        'gender',
        'birth_date',
        'phone',
        'address',
        'neighborhood',
        'village',
        'eps_code',
        'eps_name',
        'status'
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    /**
     * Relación polimórfica con seguimientos mensuales
     */
    public function monthlyFollowups(): MorphMany
    {
        return $this->morphMany(MonthlyFollowup::class, 'followupable');
    }

    /**
     * Obtener la edad calculada
     */
    public function getAgeAttribute(): int
    {
        return $this->birth_date ? $this->birth_date->age : 0;
    }

    /**
     * Obtener el último seguimiento
     */
    public function getLatestFollowupAttribute()
    {
        return $this->monthlyFollowups()->latest('followup_date')->first();
    }

    /**
     * Verificar si tiene seguimiento reciente (últimos 30 días)
     */
    public function hasRecentFollowup(): bool
    {
        return $this->monthlyFollowups()
            ->where('followup_date', '>=', now()->subDays(30))
            ->exists();
    }

    /**
     * Obtener seguimientos completados
     */
    public function getCompletedFollowupsCount(): int
    {
        return $this->monthlyFollowups()
            ->where('status', 'completed')
            ->count();
    }

    /**
     * Obtener seguimientos pendientes
     */
    public function getPendingFollowupsCount(): int
    {
        return $this->monthlyFollowups()
            ->where('status', 'pending')
            ->count();
    }

    /**
     * Verificar si el paciente está activo
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Scope para pacientes activos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope para pacientes con seguimientos pendientes
     */
    public function scopeWithPendingFollowups($query)
    {
        return $query->whereHas('monthlyFollowups', function ($q) {
            $q->where('status', 'pending');
        });
    }

    /**
     * Scope para pacientes sin seguimiento reciente
     */
    public function scopeWithoutRecentFollowup($query, $days = 45)
    {
        return $query->whereDoesntHave('monthlyFollowups', function ($q) use ($days) {
            $q->where('followup_date', '>=', now()->subDays($days));
        });
    }
}