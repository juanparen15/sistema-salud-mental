<?php

namespace App\Policies;

use App\Models\MentalDisorder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MentalDisorderPolicy
{
    use HandlesAuthorization;
    
    // public function before(User $user, $ability): ?bool
    // {
    //     if ($user->hasRole('admin')) {
    //         return true;
    //     }
        
    //     return null;
    // }
    
    // public function viewAny(User $user): bool
    // {
    //     return $user->hasPermission('view_mental_disorders');
    // }
    
    // public function view(User $user, MentalDisorder $mentalDisorder): bool
    // {
    //     return $user->hasPermission('view_mental_disorders');
    // }
    
    // public function create(User $user): bool
    // {
    //     return $user->hasPermission('create_mental_disorders');
    // }
    
    // public function update(User $user, MentalDisorder $mentalDisorder): bool
    // {
    //     // Solo puede editar si tiene permiso y es el creador o es coordinador
    //     if (!$user->hasPermission('edit_mental_disorders')) {
    //         return false;
    //     }
        
    //     return $user->hasRole('coordinator') || $mentalDisorder->created_by === $user->id;
    // }
    
    // public function delete(User $user, MentalDisorder $mentalDisorder): bool
    // {
    //     return $user->hasPermission('delete_mental_disorders') && 
    //            $user->hasRole('coordinator');
    // }
}