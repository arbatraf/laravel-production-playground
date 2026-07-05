<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\User;

class AuditEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function view(User $user, AuditEvent $auditEvent): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuditEvent $auditEvent): bool
    {
        return false;
    }

    public function delete(User $user, AuditEvent $auditEvent): bool
    {
        return false;
    }

    public function massDelete(User $user): bool
    {
        return false;
    }

    public function restore(User $user, AuditEvent $auditEvent): bool
    {
        return false;
    }

    public function forceDelete(User $user, AuditEvent $auditEvent): bool
    {
        return false;
    }
}
