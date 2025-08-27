<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SubstanceConsumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'admission_date',
        'admission_via',
        'diagnosis',
        'substances_used',
        'consumption_level',
        'additional_observation',
        'status',
        'created_by',
    ];

    protected $casts = [
        'admission_date' => 'datetime',
        'substances_used' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function followups(): MorphMany
    {
        return $this->morphMany(MonthlyFollowup::class, 'followupable');
    }
}