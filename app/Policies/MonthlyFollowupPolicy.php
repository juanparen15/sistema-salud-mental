<?php

// app/Policies/MonthlyFollowupPolicy.php
namespace App\Policies;

use App\Models\User;
use App\Models\MonthlyFollowup;

class MonthlyFollowupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_followups') ||
            $user->can('view_any_followups') ||
            $user->can('view_all_followups');
    }

    public function view(User $user, MonthlyFollowup $followup): bool
    {
        // Puede ver todos los seguimientos
        if ($user->can('view_all_followups')) {
            return true;
        }

        // Puede ver seguimientos de sus pacientes asignados
        if ($user->can('view_any_followups')) {
            return $this->belongsToAssignedPatient($user, $followup);
        }

        // Solo puede ver seguimientos creados por él
        return $followup->performed_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_followups');
    }

    public function update(User $user, MonthlyFollowup $followup): bool
    {
        // Puede editar todos los seguimientos
        if ($user->can('edit_all_followups')) {
            return true;
        }

        // Solo puede editar seguimientos creados por él
        if ($user->can('edit_followups')) {
            return $followup->performed_by === $user->id;
        }

        return false;
    }

    public function delete(User $user, MonthlyFollowup $followup): bool
    {
        return $user->can('delete_followups');
    }

    public function export(User $user): bool
    {
        return $user->can('export_followups');
    }

    private function belongsToAssignedPatient(User $user, MonthlyFollowup $followup): bool
    {
        if (!$followup->followupable || !$followup->followupable->patient) {
            return false;
        }

        return $followup->followupable->patient->assigned_to === $user->id;
    }
}
