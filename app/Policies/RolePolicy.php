<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function update(User $user, Role $role): bool
    {
        // No permitir editar roles críticos a menos que sea super_admin
        if (in_array($role->name, ['super_admin']) && !$user->hasRole('super_admin')) {
            return false;
        }

        return $user->hasRole(['super_admin', 'admin']);
    }

    public function delete(User $user, Role $role): bool
    {
        // No permitir eliminar roles del sistema
        $systemRoles = ['super_admin', 'admin', 'coordinator', 'psychologist', 'social_worker', 'assistant'];
        if (in_array($role->name, $systemRoles)) {
            return false;
        }

        return $user->hasRole(['super_admin', 'admin']);
    }

    public function managePermissions(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }
}
