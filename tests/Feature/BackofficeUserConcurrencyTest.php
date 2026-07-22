<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Users\DeleteBackofficeUserAction;
use App\Actions\Users\SaveBackofficeUserAction;
use App\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BackofficeUserConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_concurrent_demotion_keeps_one_administrator(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('MySQL is required.');
        }

        $firstAdmin = User::factory()->admin()->create();
        $secondAdmin = User::factory()->admin()->create();
        $secondAdmin->forceFill(['role' => UserRole::Manager]);

        $mysqlConfig = config('database.connections.mysql');
        $this->assertIsArray($mysqlConfig);
        config()->set('database.connections.mysql_concurrent', $mysqlConfig);

        $concurrentConnection = DB::connection('mysql_concurrent');
        $concurrentConnection->beginTransaction();

        try {
            $concurrentConnection->table('users')
                ->where('id', $firstAdmin->getKey())
                ->lockForUpdate()
                ->first(['id']);

            $concurrentConnection->table('users')
                ->where('id', $firstAdmin->getKey())
                ->update(['role' => UserRole::Manager->value]);

            DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
            $this->actingAs($secondAdmin->fresh(), 'backoffice');

            try {
                app(SaveBackofficeUserAction::class)($secondAdmin, []);
                $this->fail('Concurrent administrator lock was not enforced.');
            } catch (QueryException $e) {
                $this->assertSame(1205, $e->errorInfo[1] ?? null);
            }

            $concurrentConnection->commit();

            $this->assertThrows(
                fn (): User => app(SaveBackofficeUserAction::class)($secondAdmin, []),
                ValidationException::class,
                'At least one admin is required.',
            );

            $this->assertSame(
                1,
                User::query()->where('role', UserRole::Admin->value)->count(),
            );
            $this->assertSame(UserRole::Manager, $firstAdmin->fresh()->role);
            $this->assertSame(UserRole::Admin, $secondAdmin->fresh()->role);
        } finally {
            if ($concurrentConnection->transactionLevel() > 0) {
                $concurrentConnection->rollBack();
            }

            DB::statement('SET SESSION innodb_lock_wait_timeout = 50');
            DB::purge('mysql_concurrent');
        }
    }

    public function test_concurrent_actor_demotion_prevents_user_delete(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('MySQL is required.');
        }

        $target = User::factory()->admin()->create();
        $actor = User::factory()->admin()->create();

        $mysqlConfig = config('database.connections.mysql');
        $this->assertIsArray($mysqlConfig);
        config()->set('database.connections.mysql_concurrent', $mysqlConfig);

        $concurrentConnection = DB::connection('mysql_concurrent');
        $concurrentConnection->beginTransaction();

        try {
            $concurrentConnection->table('users')
                ->where('id', $actor->getKey())
                ->lockForUpdate()
                ->first(['id']);

            $concurrentConnection->table('users')
                ->where('id', $actor->getKey())
                ->update(['role' => UserRole::Manager->value]);

            DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
            $this->actingAs($actor, 'backoffice');

            try {
                app(DeleteBackofficeUserAction::class)($target);
                $this->fail('Concurrent administrator lock was not enforced.');
            } catch (QueryException $e) {
                $this->assertSame(1205, $e->errorInfo[1] ?? null);
            }

            $concurrentConnection->commit();

            $this->assertThrows(
                fn (): bool => app(DeleteBackofficeUserAction::class)($target),
                AuthorizationException::class,
            );

            $this->assertDatabaseHas('users', ['id' => $target->getKey()]);
            $this->assertSame(UserRole::Manager, $actor->fresh()->role);
            $this->assertSame(1, User::query()->where('role', UserRole::Admin->value)->count());
        } finally {
            if ($concurrentConnection->transactionLevel() > 0) {
                $concurrentConnection->rollBack();
            }

            DB::statement('SET SESSION innodb_lock_wait_timeout = 50');
            DB::purge('mysql_concurrent');
        }
    }

    public function test_create_retry_uses_a_fresh_model_instance(): void
    {
        $actor = User::factory()->admin()->create();
        $submittedUser = new User;
        $submittedUser->forceFill([
            'name' => 'Retried user',
            'email' => 'retried-user@example.com',
            'role' => UserRole::Viewer,
            'password' => 'new-password',
        ]);
        $attempt = 0;

        User::created(static function (User $user) use (&$attempt): void {
            if ($user->email === 'retried-user@example.com' && $attempt++ === 0) {
                throw new \RuntimeException('Deadlock found when trying to get lock');
            }
        });

        $this->actingAs($actor, 'backoffice');

        $user = app(SaveBackofficeUserAction::class)($submittedUser, []);

        $this->assertNotSame($submittedUser, $user);
        $this->assertSame(2, $attempt);
        $this->assertDatabaseHas('users', ['email' => 'retried-user@example.com']);
        $this->assertDatabaseCount('audit_events', 1);
        $this->assertSame($user->getKey(), AuditEvent::query()->sole()->subject_id);
    }

    public function test_unrelated_administrator_lock_does_not_block_other_user_changes(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('MySQL is required.');
        }

        $actor = User::factory()->admin()->create();
        $demotedAdministrator = User::factory()->admin()->create();
        $unrelatedAdministrator = User::factory()->admin()->create();
        $deletedAdministrator = User::factory()->admin()->create();
        $demotedAdministrator->forceFill(['role' => UserRole::Manager]);

        $mysqlConfig = config('database.connections.mysql');
        $this->assertIsArray($mysqlConfig);
        config()->set('database.connections.mysql_concurrent', $mysqlConfig);

        $concurrentConnection = DB::connection('mysql_concurrent');
        $concurrentConnection->beginTransaction();

        try {
            $concurrentConnection->table('users')
                ->where('id', $unrelatedAdministrator->getKey())
                ->lockForUpdate()
                ->first(['id']);

            DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
            $this->actingAs($actor, 'backoffice');

            app(SaveBackofficeUserAction::class)($demotedAdministrator, []);
            app(DeleteBackofficeUserAction::class)($deletedAdministrator);

            $this->assertSame(UserRole::Manager, $demotedAdministrator->fresh()->role);
            $this->assertDatabaseMissing('users', ['id' => $deletedAdministrator->getKey()]);
            $this->assertSame(UserRole::Admin, $unrelatedAdministrator->fresh()->role);
        } finally {
            $concurrentConnection->rollBack();
            DB::statement('SET SESSION innodb_lock_wait_timeout = 50');
            DB::purge('mysql_concurrent');
        }
    }
}
