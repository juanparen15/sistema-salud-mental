<?php

// REGISTRAR POLÍTICAS
// ================================

// app/Providers/AuthServiceProvider.php
namespace App\Providers;

use App\Models\Patient;
use App\Models\MonthlyFollowup;
use App\Models\MentalDisorder;
use App\Models\SuicideAttempt;
use App\Models\SubstanceConsumption;
use App\Policies\PatientPolicy;
use App\Policies\MonthlyFollowupPolicy;
use App\Policies\MentalDisorderPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Patient::class => PatientPolicy::class,
        MonthlyFollowup::class => MonthlyFollowupPolicy::class,
        MentalDisorder::class => MentalDisorderPolicy::class,
        SuicideAttempt::class => MentalDisorderPolicy::class, // Reutilizar la misma lógica
        SubstanceConsumption::class => MentalDisorderPolicy::class, // Reutilizar la misma lógica
    ];

    public function boot(): void
    {
        //
    }
}
