<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Task\Pages;

use App\Models\Task;
use App\MoonShine\Resources\Task\TaskResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * @extends IndexPage<TaskResource>
 */
final class TaskIndexPage extends IndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Company', 'company_id', static fn (Task $task): string => $task->company->name),
            Text::make('Contact', 'contact_id', static fn (Task $task): string => $task->contact === null
                ? ''
                : trim("{$task->contact->first_name} {$task->contact->last_name}")),
            Text::make('Title', 'title')->sortable(),
            Text::make('Status', 'status', static fn (Task $task): string => $task->status->label()),
            Text::make('Priority', 'priority', static fn (Task $task): string => $task->priority->label()),
            Text::make('Assignee', 'assigned_to_user_id', static fn (Task $task): string => (string) data_get($task->assignedTo, 'name', '')),
            Date::make('Due', 'due_at')->format('Y-m-d H:i')->sortable(),
        ];
    }
}
