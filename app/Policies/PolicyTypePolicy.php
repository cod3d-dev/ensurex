<?php

namespace App\Policies;

use App\Models\PolicyType;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use App\Enums\UserRoles;

class PolicyTypePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRoles::Admin;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PolicyType $policyType): bool
    {
        return $user->role === UserRoles::Admin || $user->role === UserRoles::Supervisor;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRoles::Admin;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PolicyType $policyType): bool
    {
        return $user->role === UserRoles::Admin;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PolicyType $policyType): bool
    {
        return $user->role === UserRoles::Admin;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PolicyType $policyType): bool
    {
        return $user->role === UserRoles::Admin;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PolicyType $policyType): bool
    {
        return $user->role === UserRoles::Admin;
    }
}
