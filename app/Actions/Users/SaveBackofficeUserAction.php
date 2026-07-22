<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Audit\RecordBackofficeResourceAuditAction;
use App\Enums\UserRole;
use App\Http\Middleware\AssignRequestId;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use LogicException;
use MoonShine\Laravel\MoonShineAuth;

final readonly class SaveBackofficeUserAction
{
    public function __construct(
        private RecordBackofficeResourceAuditAction $recordAudit,
        private EnsureAdministratorRemainsAction $ensureAdministratorRemains,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(User $submittedUser, array $data): User
    {
        $actor = MoonShineAuth::getGuard()->user();

        if (! $actor instanceof User) {
            throw new LogicException('Backoffice user is missing.');
        }

        $attributes = $this->submittedAttributes($submittedUser, $data);
        $requestId = AssignRequestId::current(request());
        $actorId = $actor->getKey();

        return $submittedUser->getConnection()->transaction(function () use (
            $submittedUser,
            $attributes,
            $actorId,
            $requestId,
        ): User {
            $actor = $submittedUser->newQuery()
                ->lockForUpdate()
                ->findOrFail($actorId);

            if ($submittedUser->getKey() === null) {
                Gate::forUser($actor)->authorize('create', User::class);

                $user = $submittedUser->newInstance();
                $user->forceFill($attributes)->saveOrFail();
                $this->recordAudit->created($user, $actor, $requestId);

                return $user;
            }

            $proposedRole = UserRole::from($attributes['role']);
            $user = $submittedUser->newQuery()
                ->lockForUpdate()
                ->findOrFail($submittedUser->getKey());

            Gate::forUser($actor)->authorize('update', $user);

            if ($user->role === UserRole::Admin
                && $proposedRole !== UserRole::Admin
            ) {
                ($this->ensureAdministratorRemains)($actor, $user);
            }

            $user->forceFill($attributes)->saveOrFail();
            $this->recordAudit->updated($user, $actor, $requestId);

            return $user;
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{name: string, email: string, role: string, password?: string}
     */
    private function submittedAttributes(User $user, array $data): array
    {
        $role = $data['role'] ?? $user->role;

        $attributes = [
            'name' => (string) ($data['name'] ?? $user->getAttribute('name')),
            'email' => (string) ($data['email'] ?? $user->getAttribute('email')),
            'role' => $role instanceof UserRole ? $role->value : (string) $role,
        ];

        if ($user->isDirty('password')) {
            $attributes['password'] = (string) $user->getAttribute('password');
        }

        return $attributes;
    }
}
