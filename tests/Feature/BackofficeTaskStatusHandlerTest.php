<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AuditEventType;
use App\Enums\TaskStatus;
use App\Models\AuditEvent;
use App\Models\Task;
use App\Models\User;
use App\MoonShine\Resources\Task\Pages\TaskIndexPage;
use App\MoonShine\Resources\Task\TaskResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Crud\Handlers\Handler;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BackofficeTaskStatusHandlerTest extends TestCase
{
    use RefreshDatabase;

    private const array HANDLER_LABELS = [
        'Reopen',
        'Start',
        'Wait',
        'Complete',
        'Cancel',
    ];

    public function test_task_status_handlers_have_unique_aliases_and_stay_out_of_top_actions(): void
    {
        $manager = User::factory()->manager()->create();
        $this->actingAs($manager, 'backoffice');

        $handlers = $this->resource()->getHandlers();
        $aliases = $handlers
            ->map(static fn (Handler $handler): string => $handler->getUriKey())
            ->values()
            ->all();

        $this->assertSame([
            'task-open',
            'task-start',
            'task-wait',
            'task-complete',
            'task-cancel',
        ], $aliases);
        $this->assertCount(5, array_unique($aliases));
        $this->assertTrue($handlers->getButtons()->onlyVisible()->isEmpty());
    }

    /**
     * @param  list<string>  $expected
     */
    #[DataProvider('visibleStatusButtons')]
    public function test_row_buttons_follow_task_transition_rules(TaskStatus $status, array $expected): void
    {
        $manager = User::factory()->manager()->create();
        $task = Task::factory()->create([
            'status' => $status,
            'completed_at' => $status->isClosed() ? now() : null,
        ]);
        $this->actingAs($manager, 'backoffice');

        $resource = $this->resource();
        $page = $resource->getIndexPage();
        $this->assertInstanceOf(TaskIndexPage::class, $page);

        $labels = $page->getButtons()
            ->fill($resource->getCaster()->cast($task))
            ->onlyVisible()
            ->map(static fn (ActionButtonContract $button): string => $button->getLabel())
            ->all();

        $this->assertSame($expected, array_values(array_intersect($labels, self::HANDLER_LABELS)));
    }

    /**
     * @return iterable<string, array{TaskStatus, list<string>}>
     */
    public static function visibleStatusButtons(): iterable
    {
        yield 'open' => [TaskStatus::Open, ['Start', 'Wait', 'Complete', 'Cancel']];
        yield 'in progress' => [TaskStatus::InProgress, ['Wait', 'Complete', 'Cancel']];
        yield 'waiting' => [TaskStatus::Waiting, ['Reopen', 'Start', 'Cancel']];
        yield 'done' => [TaskStatus::Done, []];
        yield 'canceled' => [TaskStatus::Canceled, []];
    }

    public function test_viewer_does_not_receive_task_status_buttons(): void
    {
        $viewer = User::factory()->viewer()->create();
        $task = Task::factory()->create();
        $this->actingAs($viewer, 'backoffice');

        $resource = $this->resource();
        $page = $resource->getIndexPage();
        $this->assertInstanceOf(TaskIndexPage::class, $page);

        $labels = $page->getButtons()
            ->fill($resource->getCaster()->cast($task))
            ->onlyVisible()
            ->map(static fn (ActionButtonContract $button): string => $button->getLabel())
            ->all();

        $this->assertSame([], array_values(array_intersect($labels, self::HANDLER_LABELS)));
    }

    public function test_terminal_status_buttons_require_confirmation_and_refresh_the_list(): void
    {
        $manager = User::factory()->manager()->create();
        $task = Task::factory()->create();
        $this->actingAs($manager, 'backoffice');

        $resource = $this->resource();
        $buttons = $resource->getHandlers()
            ->getButtons()
            ->fill($resource->getCaster()->cast($task))
            ->keyBy(static fn (ActionButtonContract $button): string => $button->getLabel());

        $start = $buttons->get('Start');
        $complete = $buttons->get('Complete');
        $cancel = $buttons->get('Cancel');

        $this->assertInstanceOf(ActionButtonContract::class, $start);
        $this->assertInstanceOf(ActionButtonContract::class, $complete);
        $this->assertInstanceOf(ActionButtonContract::class, $cancel);
        $this->assertTrue($start->isAsync());
        $this->assertSame('post', $start->getAttribute('data-async-method'));
        $this->assertSame($resource->getListEventName(), $start->getAttribute('data-async-events'));
        $this->assertTrue($complete->hasComponent());
        $this->assertTrue($cancel->hasComponent());
        $this->assertFalse($complete->isAsync());
        $this->assertFalse($cancel->isAsync());
    }

    public function test_task_status_handler_requires_the_backoffice_guard(): void
    {
        $task = Task::factory()->create();
        $url = $this->handlerUrl('task-start', $task);

        $this->post($url)->assertRedirect(route('moonshine.login'));

        $webUser = User::factory()->admin()->create();

        $this->actingAs($webUser)
            ->post($url)
            ->assertRedirect(route('moonshine.login'));

        $this->assertSame(TaskStatus::Open, $task->fresh()->status);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_viewer_cannot_call_task_status_handler_directly(): void
    {
        $viewer = User::factory()->viewer()->create();
        $task = Task::factory()->create();

        $this->actingAs($viewer, 'backoffice')
            ->postJson($this->handlerUrl('task-start', $task))
            ->assertForbidden()
            ->assertJsonPath('message', 'Access denied.');

        $this->assertSame(TaskStatus::Open, $task->fresh()->status);
        $this->assertDatabaseCount('audit_events', 0);
    }

    #[DataProvider('authorizedRoles')]
    public function test_authorized_roles_can_change_task_status(string $role): void
    {
        $actor = $role === 'admin'
            ? User::factory()->admin()->create()
            : User::factory()->manager()->create();
        $task = Task::factory()->create();
        $requestId = (string) Str::uuid();

        $response = $this->actingAs($actor, 'backoffice')
            ->withHeader('X-Request-ID', $requestId)
            ->postJson($this->handlerUrl('task-start', $task));

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Task status updated.')
            ->assertHeader('X-Request-ID', $requestId);

        $event = AuditEvent::query()->sole();
        $task = $task->fresh();

        $this->assertSame(TaskStatus::InProgress, $task->status);
        $this->assertNull($task->completed_at);
        $this->assertTrue($event->user->is($actor));
        $this->assertTrue($event->subject->is($task));
        $this->assertSame('open', $event->properties['from_status'] ?? null);
        $this->assertSame('in_progress', $event->properties['to_status'] ?? null);
        $this->assertSame($requestId, $event->request_id);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function authorizedRoles(): iterable
    {
        yield 'admin' => ['admin'];
        yield 'manager' => ['manager'];
    }

    #[DataProvider('handlerTargets')]
    public function test_handler_alias_maps_to_server_target_status(
        string $alias,
        TaskStatus $from,
        TaskStatus $to,
    ): void {
        $manager = User::factory()->manager()->create();
        $task = Task::factory()->create([
            'status' => $from,
            'completed_at' => $from->isClosed() ? now() : null,
        ]);
        $forgedStatus = $to === TaskStatus::Canceled
            ? TaskStatus::Done
            : TaskStatus::Canceled;

        $this->actingAs($manager, 'backoffice')
            ->postJson($this->handlerUrl($alias, $task), [
                'status' => $forgedStatus->value,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Task status updated.');

        $task = $task->fresh();
        $event = AuditEvent::query()->sole();

        $this->assertSame($to, $task->status);
        $this->assertSame($to->isClosed(), $task->completed_at !== null);
        $this->assertSame(AuditEventType::TaskStatusChanged->value, $event->event_type);
        $this->assertTrue($event->user->is($manager));
        $this->assertTrue($event->subject->is($task));
        $this->assertSame($from->value, $event->properties['from_status'] ?? null);
        $this->assertSame($to->value, $event->properties['to_status'] ?? null);
    }

    /**
     * @return iterable<string, array{string, TaskStatus, TaskStatus}>
     */
    public static function handlerTargets(): iterable
    {
        yield 'reopen' => ['task-open', TaskStatus::Waiting, TaskStatus::Open];
        yield 'start' => ['task-start', TaskStatus::Open, TaskStatus::InProgress];
        yield 'wait' => ['task-wait', TaskStatus::Open, TaskStatus::Waiting];
        yield 'complete' => ['task-complete', TaskStatus::InProgress, TaskStatus::Done];
        yield 'cancel' => ['task-cancel', TaskStatus::Open, TaskStatus::Canceled];
    }

    /**
     * @param  array<string, string>  $query
     * @param  array<string, string>  $parameters
     * @param  array<string, string>  $server
     */
    #[DataProvider('nonPostRequests')]
    public function test_task_status_handler_accepts_post_only(
        string $method,
        array $query,
        array $parameters,
        array $server,
    ): void {
        $manager = User::factory()->manager()->create();
        $task = Task::factory()->create();
        $url = $this->handlerUrl('task-start', $task)
            .($query === [] ? '' : '&'.http_build_query($query));
        $this->actingAs($manager, 'backoffice');

        $this->call(
            $method,
            $url,
            $parameters,
            server: ['HTTP_ACCEPT' => 'application/json', ...$server],
        )
            ->assertStatus(405)
            ->assertHeader('Allow', 'POST');

        $this->assertSame(TaskStatus::Open, $task->fresh()->status);
        $this->assertDatabaseCount('audit_events', 0);
    }

    /**
     * @return iterable<string, array{
     *     string,
     *     array<string, string>,
     *     array<string, string>,
     *     array<string, string>
     * }>
     */
    public static function nonPostRequests(): iterable
    {
        yield 'get' => ['GET', [], [], []];
        yield 'head' => ['HEAD', [], [], []];
        yield 'options' => ['OPTIONS', [], [], []];
        yield 'put' => ['PUT', [], [], []];
        yield 'patch' => ['PATCH', [], [], []];
        yield 'delete' => ['DELETE', [], [], []];

        foreach (['PUT', 'PATCH', 'DELETE', 'OPTIONS'] as $method) {
            $name = strtolower($method);

            yield "form override to {$name}" => ['POST', [], ['_method' => $method], []];
            yield "query override to {$name}" => ['POST', ['_method' => $method], [], []];
            yield "header override to {$name}" => [
                'POST',
                [],
                [],
                ['HTTP_X_HTTP_METHOD_OVERRIDE' => $method],
            ];
        }
    }

    #[DataProvider('invalidTaskIds')]
    public function test_task_status_handler_rejects_invalid_task_id(string $query): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager, 'backoffice')
            ->postJson($this->handlerUrl('task-start').$query)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Invalid task.');

        $this->assertDatabaseCount('audit_events', 0);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidTaskIds(): iterable
    {
        yield 'missing' => [''];
        yield 'zero' => ['?task_id=0'];
        yield 'negative' => ['?task_id=-1'];
        yield 'array' => ['?task_id%5B0%5D=1'];
    }

    public function test_task_status_handler_hides_missing_and_archived_tasks(): void
    {
        $manager = User::factory()->manager()->create();
        $task = Task::factory()->create();
        $task->delete();
        $this->actingAs($manager, 'backoffice');

        $this->postJson($this->handlerUrl('task-start').'?task_id=999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Task not found.');

        $this->postJson($this->handlerUrl('task-start', $task))
            ->assertNotFound()
            ->assertJsonPath('message', 'Task not found.');

        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_request_body_cannot_override_row_or_target_status(): void
    {
        $manager = User::factory()->manager()->create();
        $selectedTask = Task::factory()->create();
        $bodyTask = Task::factory()->create();

        $this->actingAs($manager, 'backoffice')
            ->postJson($this->handlerUrl('task-start', $selectedTask), [
                'task_id' => $bodyTask->getKey(),
                'status' => TaskStatus::Done->value,
            ])
            ->assertOk();

        $this->assertSame(TaskStatus::InProgress, $selectedTask->fresh()->status);
        $this->assertSame(TaskStatus::Open, $bodyTask->fresh()->status);
        $this->assertSame('in_progress', AuditEvent::query()->sole()->properties['to_status'] ?? null);
    }

    public function test_invalid_transition_returns_a_generic_conflict(): void
    {
        $manager = User::factory()->manager()->create();
        $task = Task::factory()->done()->create();

        $response = $this->actingAs($manager, 'backoffice')
            ->postJson($this->handlerUrl('task-start', $task));

        $response
            ->assertConflict()
            ->assertJsonPath('message', 'Status cannot be changed.')
            ->assertDontSee('InvalidArgumentException')
            ->assertDontSee('Invalid task status transition');

        $this->assertSame(TaskStatus::Done, $task->fresh()->status);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_same_target_requests_are_idempotent(): void
    {
        $manager = User::factory()->manager()->create();
        $task = Task::factory()->create();
        $url = $this->handlerUrl('task-open', $task);
        $this->actingAs($manager, 'backoffice');

        $this->postJson($url)->assertOk();
        $this->postJson($url)->assertOk();

        $this->assertSame(TaskStatus::Open, $task->fresh()->status);
        $this->assertDatabaseCount('audit_events', 0);
    }

    private function resource(): TaskResource
    {
        $resource = moonshine()->getResources()->findByClass(TaskResource::class);

        $this->assertInstanceOf(TaskResource::class, $resource);

        return $resource;
    }

    private function handlerUrl(string $alias, ?Task $task = null): string
    {
        $handler = $this->resource()->getHandlers()->findByUri($alias);

        $this->assertInstanceOf(Handler::class, $handler);

        if ($task === null) {
            return $handler->getUrl();
        }

        return $handler->getUrl().'?'.http_build_query(['task_id' => $task->getKey()]);
    }
}
