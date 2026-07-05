<?php

namespace App\Actions\Tasks;

use App\Actions\Audit\RecordAuditEventAction;
use App\Enums\AuditEventType;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class ChangeTaskStatusAction
{
    private RecordAuditEventAction $recordAuditEvent;

    public function __construct(?RecordAuditEventAction $recordAuditEvent = null)
    {
        $this->recordAuditEvent = $recordAuditEvent ?? new RecordAuditEventAction;
    }

    public function __invoke(Task $task, TaskStatus $status, ?User $user = null, ?string $requestId = null): Task
    {
        if (! $task->status->canTransitionTo($status)) {
            throw new InvalidArgumentException('Invalid task status transition.');
        }

        if ($task->status === $status) {
            return $task;
        }

        $fromStatus = $task->status;

        return DB::transaction(function () use ($task, $status, $user, $requestId, $fromStatus): Task {
            $task->forceFill([
                'status' => $status,
                'completed_at' => $status->isClosed() ? now() : null,
            ])->save();

            ($this->recordAuditEvent)(
                eventType: AuditEventType::TaskStatusChanged,
                description: sprintf('Task status changed from %s to %s.', $fromStatus->value, $status->value),
                user: $user,
                subject: $task,
                properties: [
                    'from_status' => $fromStatus->value,
                    'to_status' => $status->value,
                ],
                requestId: $requestId,
            );

            return $task->refresh();
        });
    }
}
