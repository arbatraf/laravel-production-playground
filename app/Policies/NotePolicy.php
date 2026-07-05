<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Note;
use App\Models\User;

class NotePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager, UserRole::Viewer], true);
    }

    public function view(User $user, Note $note): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Manager], true);
    }

    public function update(User $user, Note $note): bool
    {
        return $user->role === UserRole::Admin
            || ($user->role === UserRole::Manager && $user->is($note->author));
    }

    public function delete(User $user, Note $note): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function massDelete(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function restore(User $user, Note $note): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function forceDelete(User $user, Note $note): bool
    {
        return $user->role === UserRole::Admin;
    }
}
