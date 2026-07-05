<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager, UserRole::Viewer], true);
    }

    public function view(User $user, Task $task): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager], true);
    }

    public function update(User $user, Task $task): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager], true);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function massDelete(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function restore(User $user, Task $task): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return $user->role === UserRole::Admin
            && ! $task->notes()->withTrashed()->exists();
    }
}
