<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Actions\Audit\RecordAuditEventAction;
use App\Enums\AuditEventType;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

final readonly class ChangeTaskStatusAction
{
    public function __construct(private RecordAuditEventAction $recordAuditEvent) {}

    public function __invoke(Task $task, TaskStatus $status, User $user, ?string $requestId = null): Task
    {
        $taskId = $task->getKey();
        $userId = $user->getKey();

        return $task->getConnection()->transaction(function () use ($task, $taskId, $status, $userId, $requestId): Task {
            $actor = User::query()
                ->lockForUpdate()
                ->findOrFail($userId);

            $task = $task->newQuery()
                ->lockForUpdate()
                ->findOrFail($taskId);

            Gate::forUser($actor)->authorize('update', $task);

            if ($task->status === $status) {
                return $task;
            }

            if (! $task->status->canTransitionTo($status)) {
                throw new InvalidArgumentException('Invalid task status transition.');
            }

            $fromStatus = $task->status;

            $task->forceFill([
                'status' => $status,
                'completed_at' => $status->isClosed() ? now() : null,
            ])->saveOrFail();

            ($this->recordAuditEvent)(
                eventType: AuditEventType::TaskStatusChanged,
                description: sprintf('Task status changed from %s to %s.', $fromStatus->value, $status->value),
                user: $actor,
                subject: $task,
                properties: [
                    'from_status' => $fromStatus->value,
                    'to_status' => $status->value,
                ],
                requestId: $requestId,
            );

            return $task;
        }, 3);
    }
}
