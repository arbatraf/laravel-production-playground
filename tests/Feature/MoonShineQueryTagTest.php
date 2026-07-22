<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use App\MoonShine\Resources\Task\TaskResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MoonShine\Crud\QueryTags\QueryTag;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MoonShineQueryTagTest extends TestCase
{
    use RefreshDatabase;

    private const TASKS = [
        'overdue_yesterday' => 'Overdue yesterday task',
        'overdue_today' => 'Overdue today task',
        'today' => 'Due now task',
        'tomorrow' => 'Due tomorrow task',
        'done' => 'Done today task',
        'done_without_due_date' => 'Done task without due date',
        'canceled' => 'Canceled overdue task',
        'without_due_date' => 'Task without due date',
    ];

    public function test_task_query_tags_define_aliases_icons_and_default(): void
    {
        $tags = array_map(
            static fn (QueryTag $tag): array => [
                'label' => $tag->getLabel(),
                'alias' => $tag->getUri(),
                'icon' => $tag->getIconValue(),
                'default' => $tag->isDefault(),
            ],
            $this->resource()->getQueryTags(),
        );

        $this->assertSame([
            ['label' => 'All', 'alias' => 'all', 'icon' => 'list-bullet', 'default' => true],
            ['label' => 'Overdue', 'alias' => 'overdue', 'icon' => 'exclamation-triangle', 'default' => false],
            ['label' => 'Today', 'alias' => 'today', 'icon' => 'calendar-days', 'default' => false],
            ['label' => 'Done', 'alias' => 'done', 'icon' => 'check-circle', 'default' => false],
        ], $tags);
    }

    /**
     * @param  list<string>  $visible
     * @param  list<string>  $hidden
     */
    #[DataProvider('queryTagCases')]
    public function test_task_query_tags_filter_the_index(?string $alias, array $visible, array $hidden): void
    {
        $this->travelTo('2026-07-22 12:00:00');

        try {
            $viewer = User::factory()->viewer()->create();
            $this->createTasks();
            $resource = $this->resource();
            $params = $alias === null
                ? []
                : [$resource->getQueryParamName('query-tag') => $alias];

            $response = $this->actingAs($viewer, 'backoffice')
                ->get($resource->getIndexPageUrl($params))
                ->assertOk();

            foreach ($visible as $title) {
                $response->assertSeeText($title);
            }

            foreach ($hidden as $title) {
                $response->assertDontSeeText($title);
            }
        } finally {
            $this->travelBack();
        }
    }

    /**
     * @return iterable<string, array{?string, list<string>, list<string>}>
     */
    public static function queryTagCases(): iterable
    {
        yield 'default all' => [
            null,
            array_values(self::TASKS),
            [],
        ];

        yield 'explicit all' => [
            'all',
            array_values(self::TASKS),
            [],
        ];

        yield 'overdue open tasks' => [
            'overdue',
            [self::TASKS['overdue_yesterday'], self::TASKS['overdue_today']],
            [
                self::TASKS['today'],
                self::TASKS['tomorrow'],
                self::TASKS['done'],
                self::TASKS['done_without_due_date'],
                self::TASKS['canceled'],
                self::TASKS['without_due_date'],
            ],
        ];

        yield 'open tasks due today' => [
            'today',
            [self::TASKS['overdue_today'], self::TASKS['today']],
            [
                self::TASKS['overdue_yesterday'],
                self::TASKS['tomorrow'],
                self::TASKS['done'],
                self::TASKS['done_without_due_date'],
                self::TASKS['canceled'],
                self::TASKS['without_due_date'],
            ],
        ];

        yield 'done tasks' => [
            'done',
            [self::TASKS['done'], self::TASKS['done_without_due_date']],
            [
                self::TASKS['overdue_yesterday'],
                self::TASKS['overdue_today'],
                self::TASKS['today'],
                self::TASKS['tomorrow'],
                self::TASKS['canceled'],
                self::TASKS['without_due_date'],
            ],
        ];
    }

    /**
     * @param  string|list<string>  $value
     */
    #[DataProvider('invalidQueryTags')]
    public function test_invalid_query_tags_fall_back_to_the_unfiltered_index(string|array $value): void
    {
        $this->travelTo('2026-07-22 12:00:00');

        try {
            $viewer = User::factory()->viewer()->create();
            $this->createTasks();
            $resource = $this->resource();
            $query = http_build_query([
                $resource->getQueryParamName('query-tag') => $value,
            ]);

            $response = $this->actingAs($viewer, 'backoffice')
                ->get("{$resource->getIndexPageUrl()}?{$query}")
                ->assertOk();

            foreach (self::TASKS as $title) {
                $response->assertSeeText($title);
            }
        } finally {
            $this->travelBack();
        }
    }

    /**
     * @return iterable<string, array{string|list<string>}>
     */
    public static function invalidQueryTags(): iterable
    {
        yield 'unknown alias' => ['unknown'];
        yield 'array-shaped value' => [['done']];
    }

    public function test_tagged_task_index_requires_the_backoffice_guard(): void
    {
        $resource = $this->resource();
        $url = $resource->getIndexPageUrl([
            $resource->getQueryParamName('query-tag') => 'done',
        ]);

        $this->get($url)->assertRedirect(route('moonshine.login'));

        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect(route('moonshine.login'));
    }

    private function createTasks(): void
    {
        $company = Company::factory()->create();
        $assignee = User::factory()->manager()->create();
        $creator = User::factory()->admin()->create();
        $common = [
            'assigned_to_user_id' => $assignee->getKey(),
            'created_by_user_id' => $creator->getKey(),
        ];
        $tasks = [
            ['title' => self::TASKS['overdue_yesterday'], 'status' => TaskStatus::Open, 'due_at' => now()->subDay()],
            ['title' => self::TASKS['overdue_today'], 'status' => TaskStatus::Waiting, 'due_at' => now()->startOfDay()],
            ['title' => self::TASKS['today'], 'status' => TaskStatus::InProgress, 'due_at' => now()],
            ['title' => self::TASKS['tomorrow'], 'status' => TaskStatus::Open, 'due_at' => now()->addDay()->startOfDay()],
            ['title' => self::TASKS['done'], 'status' => TaskStatus::Done, 'due_at' => now()->addHour(), 'completed_at' => now()],
            ['title' => self::TASKS['done_without_due_date'], 'status' => TaskStatus::Done, 'due_at' => null, 'completed_at' => now()],
            ['title' => self::TASKS['canceled'], 'status' => TaskStatus::Canceled, 'due_at' => now()->subDay(), 'completed_at' => now()],
            ['title' => self::TASKS['without_due_date'], 'status' => TaskStatus::Open, 'due_at' => null],
        ];

        foreach ($tasks as $task) {
            Task::factory()->for($company)->create([...$common, ...$task]);
        }
    }

    private function resource(): TaskResource
    {
        $resource = moonshine()->getResources()->findByClass(TaskResource::class);

        $this->assertInstanceOf(TaskResource::class, $resource);

        return $resource;
    }
}
