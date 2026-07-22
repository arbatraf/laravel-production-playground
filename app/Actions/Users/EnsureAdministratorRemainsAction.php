<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final readonly class EnsureAdministratorRemainsAction
{
    public function __invoke(User $actor, User $removedAdministrator): void
    {
        if ($actor->role === UserRole::Admin && ! $actor->is($removedAdministrator)) {
            return;
        }

        $keyName = $removedAdministrator->getKeyName();
        $administrator = $removedAdministrator->newQuery()
            ->where('role', UserRole::Admin->value)
            ->whereKeyNot($removedAdministrator->getKey())
            ->orderBy($keyName)
            ->lockForUpdate()
            ->first([$keyName]);

        if ($administrator === null) {
            throw ValidationException::withMessages([
                'role' => 'At least one admin is required.',
            ]);
        }
    }
}
