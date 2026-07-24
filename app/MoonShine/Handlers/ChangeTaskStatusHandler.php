<?php

declare(strict_types=1);

namespace App\MoonShine\Handlers;

use App\Actions\Tasks\ChangeTaskStatusAction;
use App\Enums\TaskStatus;
use App\Http\Middleware\AssignRequestId;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Crud\Handlers\Handler;
use MoonShine\Crud\JsonResponse;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\Support\Enums\HttpMethod;
use MoonShine\Support\Enums\ToastType;
use MoonShine\UI\Components\ActionButton;
use Symfony\Component\HttpFoundation\Response;

final class ChangeTaskStatusHandler extends Handler
{
    public function __construct(string $label, private readonly TaskStatus $status)
    {
        parent::__construct($label);
    }

    public function handle(): Response
    {
        $request = request();

        if (! $request->isMethod('POST')) {
            return $this->error('Method not allowed.', Response::HTTP_METHOD_NOT_ALLOWED, ['Allow' => 'POST']);
        }

        $validator = Validator::make($request->query->all(), [
            'task_id' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->error('Invalid task.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $actor = MoonShineAuth::getGuard()->user();

        if (! $actor instanceof User) {
            return $this->error('Access denied.', Response::HTTP_FORBIDDEN);
        }

        try {
            $task = Task::query()->findOrFail((int) $validator->validated()['task_id']);

            app(ChangeTaskStatusAction::class)(
                task: $task,
                status: $this->status,
                user: $actor,
                requestId: AssignRequestId::current($request),
            );
        } catch (ModelNotFoundException) {
            return $this->error('Task not found.', Response::HTTP_NOT_FOUND);
        } catch (AuthorizationException) {
            return $this->error('Access denied.', Response::HTTP_FORBIDDEN);
        } catch (InvalidArgumentException) {
            return $this->error('Status cannot be changed.', Response::HTTP_CONFLICT);
        }

        return JsonResponse::make()->toast('Task status updated.', ToastType::SUCCESS);
    }

    public function getButton(): ActionButtonContract
    {
        $actor = MoonShineAuth::getGuard()->user();
        $url = $this->getUrl();
        $alias = $this->getUriKey();
        $events = $this->getResource() === null
            ? []
            : [$this->getResource()->getListEventName()];

        $button = ActionButton::make(
            $this->getLabel(),
            static fn (mixed $task): string => $task instanceof Task
                ? $url.'?'.http_build_query(['task_id' => $task->getKey()])
                : $url,
        )
            ->canSee(fn (mixed $task): bool => $task instanceof Task
                && $actor instanceof User
                && $task->status !== $this->status
                && $task->status->canTransitionTo($this->status)
                && Gate::forUser($actor)->allows('update', $task))
            ->showInDropdown()
            ->async(method: HttpMethod::POST, events: $events);

        if ($this->status->isClosed()) {
            $button->withConfirm(
                title: $this->status === TaskStatus::Done ? 'Complete task?' : 'Cancel task?',
                content: 'The task will be closed.',
                button: $this->getLabel(),
                name: static fn (mixed $task): string => $task instanceof Task
                    ? $alias.'-'.$task->getKey()
                    : $alias,
            );
        }

        return $this->prepareButton($button);
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function error(string $message, int $status, array $headers = []): JsonResponse
    {
        $response = JsonResponse::make()->toast($message, ToastType::ERROR);
        $response->setStatusCode($status);

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }

        return $response;
    }
}
