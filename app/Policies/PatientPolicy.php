<?php

// ================================
// POLÍTICAS DE ACCESO (POLICIES)
// ================================

// app/Policies/PatientPolicy.php
namespace App\Policies;

use App\Models\User;
use App\Models\Patient;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_patients') || $user->can('view_any_patients');
    }

    public function view(User $user, Patient $patient): bool
    {
        // Super admin y admin pueden ver todo
        if ($user->can('view_any_patients')) {
            return true;
        }

        // Solo puede ver pacientes asignados a él
        return $patient->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_patients');
    }

    public function update(User $user, Patient $patient): bool
    {
        if (!$user->can('edit_patients')) {
            return false;
        }

        // Assistant no puede editar pacientes
        if ($user->hasRole('assistant')) {
            return false;
        }

        // Si no puede ver todos los pacientes, solo puede editar los asignados a él
        if (!$user->can('view_any_patients')) {
            return $patient->assigned_to === $user->id;
        }

        return true;
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $user->can('delete_patients');
    }

    public function export(User $user): bool
    {
        return $user->can('export_patients');
    }

    public function import(User $user): bool
    {
        return $user->can('import_patients');
    }
}