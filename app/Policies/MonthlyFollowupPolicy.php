<?php

namespace App\Policies;

use App\Models\MonthlyFollowup;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MonthlyFollowupPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_followups') || 
               $user->hasRole(['admin', 'coordinator', 'psychologist']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MonthlyFollowup $monthlyFollowup): bool
    {
        // Los usuarios pueden ver sus propios seguimientos o tener permisos específicos
        return $monthlyFollowup->user_id === $user->id ||
               $user->hasPermissionTo('view_all_followups') ||
               $user->hasRole(['admin', 'coordinator']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_followups') || 
               $user->hasRole(['admin', 'coordinator', 'psychologist']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MonthlyFollowup $monthlyFollowup): bool
    {
        // Los usuarios pueden editar sus propios seguimientos recientes o tener permisos
        $isOwner = $monthlyFollowup->user_id === $user->id;
        $isRecent = $monthlyFollowup->created_at >= now()->subDays(7); // 7 días para editar
        
        return ($isOwner && $isRecent) ||
               $user->hasPermissionTo('edit_all_followups') ||
               $user->hasRole(['admin', 'coordinator']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MonthlyFollowup $monthlyFollowup): bool
    {
        // Solo admins y coordinadores pueden eliminar seguimientos
        return $user->hasPermissionTo('delete_followups') || 
               $user->hasRole(['admin', 'coordinator']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MonthlyFollowup $monthlyFollowup): bool
    {
        return $user->hasRole(['admin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MonthlyFollowup $monthlyFollowup): bool
    {
        return $user->hasRole(['admin']);
    }

    /**
     * Determine whether the user can view sensitive information.
     */
    public function viewSensitiveInfo(User $user): bool
    {
        return $user->hasPermissionTo('view_sensitive_info') ||
               $user->hasRole(['admin', 'coordinator', 'psychologist']);
    }

    /**
     * Determine whether the user can export followups.
     */
    public function export(User $user): bool
    {
        return $user->hasPermissionTo('export_followups') || 
               $user->hasRole(['admin', 'coordinator']);
    }
}