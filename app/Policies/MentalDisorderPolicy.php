<?php

// app/Policies/MentalDisorderPolicy.php
namespace App\Policies;

use App\Models\User;
use App\Models\MentalDisorder;

class MentalDisorderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_patients') || $user->can('view_any_patients');
    }

    public function view(User $user, MentalDisorder $disorder): bool
    {
        if ($user->can('view_any_patients')) {
            return true;
        }

        return $disorder->patient->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_patients') && !$user->hasRole('assistant');
    }

    public function update(User $user, MentalDisorder $disorder): bool
    {
        if (!$user->can('edit_patients') || $user->hasRole('assistant')) {
            return false;
        }

        if (!$user->can('view_any_patients')) {
            return $disorder->patient->assigned_to === $user->id;
        }

        return true;
    }

    public function delete(User $user, MentalDisorder $disorder): bool
    {
        return $user->can('delete_patients');
    }
}
