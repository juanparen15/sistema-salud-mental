<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PatientPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_patients') || 
               $user->hasRole(['admin', 'coordinator', 'psychologist']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Patient $patient): bool
    {
        return $user->hasPermissionTo('view_patients') || 
               $user->hasRole(['admin', 'coordinator', 'psychologist']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_patients') || 
               $user->hasRole(['admin', 'coordinator', 'psychologist']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Patient $patient): bool
    {
        return $user->hasPermissionTo('edit_patients') || 
               $user->hasRole(['admin', 'coordinator']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Patient $patient): bool
    {
        return $user->hasPermissionTo('delete_patients') || 
               $user->hasRole(['admin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Patient $patient): bool
    {
        return $user->hasRole(['admin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Patient $patient): bool
    {
        return $user->hasRole(['admin']);
    }

    /**
     * Determine whether the user can export patients.
     */
    public function export(User $user): bool
    {
        return $user->hasPermissionTo('export_patients') || 
               $user->hasRole(['admin', 'coordinator']);
    }

    /**
     * Determine whether the user can import patients.
     */
    public function import(User $user): bool
    {
        return $user->hasPermissionTo('import_patients') || 
               $user->hasRole(['admin', 'coordinator']);
    }
}