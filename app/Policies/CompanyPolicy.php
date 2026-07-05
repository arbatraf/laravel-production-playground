<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager, UserRole::Viewer], true);
    }

    public function view(User $user, Company $company): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager], true);
    }

    public function update(User $user, Company $company): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager], true);
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function massDelete(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function restore(User $user, Company $company): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function forceDelete(User $user, Company $company): bool
    {
        return $user->role === UserRole::Admin
            && ! $company->contacts()->withTrashed()->exists()
            && ! $company->tasks()->withTrashed()->exists()
            && ! $company->notes()->withTrashed()->exists();
    }
}
