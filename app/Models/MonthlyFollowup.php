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
    ];

    protected $casts = [
        'followup_date' => 'date',
        'next_followup' => 'date',
        'actions_taken' => 'array',
    ];

    /**
     * Relación polimórfica con el modelo seguido (Paciente, etc.)
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
     * Helper: retorna el paciente si el followupable es un Patient
     */
    public function getPatientAttribute(): ?Patient
    {
        return $this->followupable_type === Patient::class ? $this->followupable : null;
    }

    /**
     * -------- STATES --------
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function wasNotContacted(): bool
    {
        return $this->status === 'not_contacted';
    }

    public function wasRefused(): bool
    {
        return $this->status === 'refused';
    }

    /**
     * -------- ATTRIBUTES --------
     */
    public function getActionsAsStringAttribute(): string
    {
        return $this->actions_taken && is_array($this->actions_taken)
            ? implode(', ', $this->actions_taken)
            : '';
    }

    public function hasNextAppointment(): bool
    {
        return !is_null($this->next_followup);
    }

    public function isNextAppointmentOverdue(): bool
    {
        return $this->next_followup && $this->next_followup < now();
    }

    /**
     * -------- SCOPES --------
     */
    public function scopeForPeriod($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeNotContacted($query)
    {
        return $query->where('status', 'not_contacted');
    }

    public function scopeRefused($query)
    {
        return $query->where('status', 'refused');
    }

    public function scopeForPatients($query)
    {
        return $query->where('followupable_type', Patient::class);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('followup_date', '>=', now()->subDays($days));
    }
}
