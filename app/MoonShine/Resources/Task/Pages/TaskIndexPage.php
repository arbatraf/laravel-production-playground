<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Task\Pages;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\MoonShine\Handlers\ChangeTaskStatusHandler;
use App\MoonShine\Resources\Task\TaskResource;
use Illuminate\Database\Eloquent\Builder;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Crud\Handlers\Handler;
use MoonShine\Crud\QueryTags\QueryTag as BaseQueryTag;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * @extends IndexPage<TaskResource>
 */
final class TaskIndexPage extends IndexPage
{
    /**
     * @return ListOf<Handler>
     */
    protected function handlers(): ListOf
    {
        return new ListOf(Handler::class, [
            (new ChangeTaskStatusHandler('Reopen', TaskStatus::Open))->alias('task-open'),
            (new ChangeTaskStatusHandler('Start', TaskStatus::InProgress))->alias('task-start'),
            (new ChangeTaskStatusHandler('Wait', TaskStatus::Waiting))->alias('task-wait'),
            (new ChangeTaskStatusHandler('Complete', TaskStatus::Done))->alias('task-complete'),
            (new ChangeTaskStatusHandler('Cancel', TaskStatus::Canceled))->alias('task-cancel'),
        ]);
    }

    /**
     * @return ListOf<ActionButtonContract>
     */
    protected function buttons(): ListOf
    {
        return parent::buttons()->add(
            ...$this->getHandlers()->getButtons()->toArray(),
        );
    }

    /**
     * @return list<BaseQueryTag>
     */
    protected function queryTags(): array
    {
        return [
            QueryTag::make('All', static fn (Builder $query): Builder => $query)
                ->alias('all')
                ->icon('list-bullet')
                ->default(),
            QueryTag::make('Overdue', self::applyOverdue(...))
                ->alias('overdue')
                ->icon('exclamation-triangle'),
            QueryTag::make('Today', self::applyDueToday(...))
                ->alias('today')
                ->icon('calendar-days'),
            QueryTag::make('Done', self::applyDone(...))
                ->alias('done')
                ->icon('check-circle'),
        ];
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    private static function applyOverdue(Builder $query): Builder
    {
        return $query->overdue();
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    private static function applyDueToday(Builder $query): Builder
    {
        return $query->dueToday();
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    private static function applyDone(Builder $query): Builder
    {
        return $query->status(TaskStatus::Done);
    }

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
            Text::make('Title', 'title'),
            Text::make('Status', 'status', static fn (Task $task): string => $task->status->label()),
            Text::make('Priority', 'priority', static fn (Task $task): string => $task->priority->label()),
            Text::make('Assignee', 'assigned_to_user_id', static fn (Task $task): string => (string) data_get($task->assignedTo, 'name', '')),
            Date::make('Due', 'due_at')->format('Y-m-d H:i')->sortable(),
        ];
    }
}
