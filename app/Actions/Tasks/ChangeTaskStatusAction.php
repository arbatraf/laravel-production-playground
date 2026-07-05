<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Models\Task;
use InvalidArgumentException;

final readonly class ChangeTaskStatusAction
{
    public function __invoke(Task $task, TaskStatus $status): Task
    {
        if (! $task->status->canTransitionTo($status)) {
            throw new InvalidArgumentException('Invalid task status transition.');
        }

        if ($task->status === $status) {
            return $task;
        }

        $task->forceFill([
            'status' => $status,
            'completed_at' => $status->isClosed() ? now() : null,
        ])->save();

        return $task->refresh();
    }
}
