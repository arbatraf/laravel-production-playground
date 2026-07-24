<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Tasks\ChangeTaskStatusAction;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class BackofficeTaskConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_concurrent_status_change_cannot_overwrite_a_closed_task(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('MySQL is required.');
        }

        $manager = User::factory()->manager()->create();
        $task = Task::factory()->create();
        $mysqlConfig = config('database.connections.mysql');
        $this->assertIsArray($mysqlConfig);
        config()->set('database.connections.mysql_concurrent', $mysqlConfig);

        $concurrentConnection = DB::connection('mysql_concurrent');
        $concurrentConnection->beginTransaction();

        try {
            $concurrentConnection->table('tasks')
                ->where('id', $task->getKey())
                ->lockForUpdate()
                ->first(['id']);

            $concurrentConnection->table('tasks')
                ->where('id', $task->getKey())
                ->update([
                    'status' => TaskStatus::Done->value,
                    'completed_at' => now(),
                ]);

            DB::statement('SET SESSION innodb_lock_wait_timeout = 1');

            try {
                app(ChangeTaskStatusAction::class)($task, TaskStatus::Waiting, $manager);
                $this->fail('Concurrent task lock was not enforced.');
            } catch (QueryException $e) {
                $this->assertSame(1205, $e->errorInfo[1] ?? null);
            }

            $concurrentConnection->commit();

            $this->assertThrows(
                fn (): Task => app(ChangeTaskStatusAction::class)($task, TaskStatus::Waiting, $manager),
                InvalidArgumentException::class,
                'Invalid task status transition.',
            );

            $task = $task->fresh();

            $this->assertSame(TaskStatus::Done, $task->status);
            $this->assertNotNull($task->completed_at);
            $this->assertDatabaseCount('audit_events', 0);
        } finally {
            if ($concurrentConnection->transactionLevel() > 0) {
                $concurrentConnection->rollBack();
            }

            DB::statement('SET SESSION innodb_lock_wait_timeout = 50');
            DB::purge('mysql_concurrent');
        }
    }

    public function test_concurrent_actor_demotion_blocks_task_status_change(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('MySQL is required.');
        }

        $manager = User::factory()->manager()->create();
        $task = Task::factory()->create();
        $mysqlConfig = config('database.connections.mysql');
        $this->assertIsArray($mysqlConfig);
        config()->set('database.connections.mysql_concurrent', $mysqlConfig);

        $concurrentConnection = DB::connection('mysql_concurrent');
        $concurrentConnection->beginTransaction();

        try {
            $concurrentConnection->table('users')
                ->where('id', $manager->getKey())
                ->lockForUpdate()
                ->first(['id']);

            $concurrentConnection->table('users')
                ->where('id', $manager->getKey())
                ->update(['role' => UserRole::Viewer->value]);

            DB::statement('SET SESSION innodb_lock_wait_timeout = 1');

            try {
                app(ChangeTaskStatusAction::class)($task, TaskStatus::InProgress, $manager);
                $this->fail('Concurrent actor lock was not enforced.');
            } catch (QueryException $e) {
                $this->assertSame(1205, $e->errorInfo[1] ?? null);
            }

            $concurrentConnection->commit();

            $this->assertThrows(
                fn (): Task => app(ChangeTaskStatusAction::class)($task, TaskStatus::InProgress, $manager),
                AuthorizationException::class,
            );

            $this->assertSame(UserRole::Viewer, $manager->fresh()->role);
            $this->assertSame(TaskStatus::Open, $task->fresh()->status);
            $this->assertDatabaseCount('audit_events', 0);
        } finally {
            if ($concurrentConnection->transactionLevel() > 0) {
                $concurrentConnection->rollBack();
            }

            DB::statement('SET SESSION innodb_lock_wait_timeout = 50');
            DB::purge('mysql_concurrent');
        }
    }

    public function test_status_change_retry_uses_fresh_task_state(): void
    {
        $manager = User::factory()->manager()->create();
        $submittedTask = Task::factory()->create();
        $attempt = 0;

        Task::updating(static function (Task $task) use ($submittedTask, &$attempt): void {
            if ($task->is($submittedTask) && $attempt++ === 0) {
                throw new RuntimeException('Deadlock found when trying to get lock');
            }
        });

        $task = app(ChangeTaskStatusAction::class)($submittedTask, TaskStatus::InProgress, $manager);

        $this->assertNotSame($submittedTask, $task);
        $this->assertSame(2, $attempt);
        $this->assertSame(TaskStatus::InProgress, $task->status);
        $this->assertDatabaseCount('audit_events', 1);
    }
}
