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

final readonly class DeleteBackofficeUserAction
{
    public function __construct(
        private RecordBackofficeResourceAuditAction $recordAudit,
        private EnsureAdministratorRemainsAction $ensureAdministratorRemains,
    ) {}

    public function __invoke(User $submittedUser): bool
    {
        $actor = MoonShineAuth::getGuard()->user();

        if (! $actor instanceof User) {
            throw new LogicException('Backoffice user is missing.');
        }

        $requestId = AssignRequestId::current(request());
        $actorId = $actor->getKey();

        return $submittedUser->getConnection()->transaction(function () use (
            $submittedUser,
            $actorId,
            $requestId,
        ): bool {
            $actor = $submittedUser->newQuery()
                ->lockForUpdate()
                ->findOrFail($actorId);

            $user = $submittedUser->newQuery()
                ->lockForUpdate()
                ->findOrFail($submittedUser->getKey());

            Gate::forUser($actor)->authorize('delete', $user);

            if ($user->role === UserRole::Admin) {
                ($this->ensureAdministratorRemains)($actor, $user);
            }

            $this->recordAudit->deleted($user, $actor, $requestId);

            return (bool) $user->deleteOrFail();
        }, 3);
    }
}
