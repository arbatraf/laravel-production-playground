<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Task;

use App\Models\Task;
use App\Models\User;
use App\MoonShine\Resources\Concerns\AuditsResourceChanges;
use App\MoonShine\Resources\Concerns\LimitsMassDeletion;
use App\MoonShine\Resources\Task\Pages\TaskDetailPage;
use App\MoonShine\Resources\Task\Pages\TaskFormPage;
use App\MoonShine\Resources\Task\Pages\TaskIndexPage;
use Illuminate\Auth\Access\AuthorizationException;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\Laravel\Resources\ModelResource;

/**
 * @extends ModelResource<Task, TaskIndexPage, TaskFormPage, TaskDetailPage>
 */
final class TaskResource extends ModelResource
{
    use AuditsResourceChanges, LimitsMassDeletion;

    protected string $model = Task::class;

    protected string $title = 'Tasks';

    protected string $column = 'title';

    protected array $with = ['company', 'contact', 'assignedTo'];

    protected bool $withPolicy = true;

    protected function pages(): array
    {
        return [
            TaskIndexPage::class,
            TaskFormPage::class,
            TaskDetailPage::class,
        ];
    }

    /**
     * @param  DataWrapperContract<Task>  $item
     * @return DataWrapperContract<Task>
     */
    protected function beforeCreating(DataWrapperContract $item): DataWrapperContract
    {
        $user = MoonShineAuth::getGuard()->user();

        if (! $user instanceof User) {
            throw new AuthorizationException;
        }

        $item->getOriginal()->createdBy()->associate($user);

        return $item;
    }
}
