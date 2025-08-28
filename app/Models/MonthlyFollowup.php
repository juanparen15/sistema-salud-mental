<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use Illuminate\Database\Eloquent\Relations\MorphTo;

// class MonthlyFollowup extends Model
// {
//     use HasFactory;

//     protected $fillable = [
//         'followup_date',
//         'year',
//         'month',
//         'description',
//         'status',
//         'next_followup',
//         'actions_taken',
//         'performed_by',
//     ];

//     protected $casts = [
//         'followup_date' => 'date',
//         'next_followup' => 'date',
//         'actions_taken' => 'array',
//     ];

//     public function followupable(): MorphTo
//     {
//         return $this->morphTo();
//     }

//     public function performer(): BelongsTo
//     {
//         return $this->belongsTo(User::class, 'performed_by');
//     }

//     public function getMonthNameAttribute(): string
//     {
//         $months = [
//             1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 
//             4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
//             7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
//             10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
//         ];

//         return $months[$this->month] . ' ' . $this->year;
//     }
// }

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyFollowup extends Model
{
    use HasFactory;

    protected $fillable = [
        'followupable_id',
        'followupable_type',
        'followup_date',
        'year',
        'month',
        'description',
        'status',
        'next_followup',
        'actions_taken',
        'performed_by',
        'source_reference',
    ];

    protected $casts = [
        'followup_date' => 'date',
        'next_followup' => 'date',
        'actions_taken' => 'array',
        'source_reference' => 'array', // Para almacenar referencias adicionales
    ];

    /**
     * Relación polimórfica con el modelo seguido
     */
    public function followupable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Usuario que realizó el seguimiento
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Relación directa con Patient para Filament
     * CORREGIDA: Sin condición where que causaba el error
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'followupable_id');
    }

    /**
     * Relación con User para Filament (alias de performedBy)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Obtener el paciente solo si followupable_type es Patient
     */
    public function getPatientAttribute(): ?Patient
    {
        return $this->followupable_type === Patient::class ? $this->followupable : null;
    }

    /**
     * Verificar si es un seguimiento de paciente
     */
    public function isPatientFollowup(): bool
    {
        return $this->followupable_type === Patient::class;
    }

    /**
     * Verificar si el seguimiento está completado
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Verificar si el seguimiento está pendiente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Verificar si no se pudo contactar
     */
    public function wasNotContacted(): bool
    {
        return $this->status === 'not_contacted';
    }

    /**
     * Verificar si fue rechazado
     */
    public function wasRefused(): bool
    {
        return $this->status === 'refused';
    }

    /**
     * Obtener las acciones como string
     */
    public function getActionsAsStringAttribute(): string
    {
        if (!$this->actions_taken || !is_array($this->actions_taken)) {
            return '';
        }

        return implode(', ', $this->actions_taken);
    }

    /**
     * Verificar si tiene próxima cita programada
     */
    public function hasNextAppointment(): bool
    {
        return !is_null($this->next_followup);
    }

    /**
     * Verificar si la próxima cita está vencida
     */
    public function isNextAppointmentOverdue(): bool
    {
        if (!$this->next_followup) {
            return false;
        }

        return $this->next_followup < now();
    }

    // ==================== SCOPES ====================

    /**
     * Scope para seguimientos por año y mes
     */
    public function scopeForPeriod($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    /**
     * Scope para seguimientos pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope para seguimientos completados
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope para seguimientos no contactados
     */
    public function scopeNotContacted($query)
    {
        return $query->where('status', 'not_contacted');
    }

    /**
     * Scope para seguimientos rechazados
     */
    public function scopeRefused($query)
    {
        return $query->where('status', 'refused');
    }

    /**
     * Scope para seguimientos de pacientes SOLAMENTE
     */
    public function scopeForPatients($query)
    {
        return $query->where('followupable_type', Patient::class);
    }

    /**
     * Scope para seguimientos recientes
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('followup_date', '>=', now()->subDays($days));
    }

    /**
     * Scope para seguimientos con pacientes cargados
     */
    public function scopeWithPatients($query)
    {
        return $query->where('followupable_type', Patient::class)
            ->with(['followupable', 'user']);
    }

    /**
     * Scope para el año actual
     */
    public function scopeCurrentYear($query)
    {
        return $query->where('year', now()->year);
    }

    /**
     * Scope para seguimientos por estado específico
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function getSourceInfo(): ?array
    {
        if (!$this->source_reference) {
            return null;
        }

        $source = $this->source_reference;

        if ($source['type'] === 'mental_disorder') {
            $mentalDisorder = \App\Models\MentalDisorder::find($source['id']);
            if ($mentalDisorder) {
                return [
                    'type' => 'Trastorno Mental',
                    'description' => $mentalDisorder->diagnosis_description,
                    'code' => $mentalDisorder->diagnosis_code,
                    'model' => $mentalDisorder
                ];
            }
        }

        return null;
    }
}
